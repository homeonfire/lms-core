<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeworkController extends Controller
{
    public function submit(Request $request, Homework $homework)
    {
        // 1. Подготовка данных
        // Мы берем присланные поля из формы (они лежат в массиве 'fields')
        $inputData = $request->input('fields', []);
        $submissionContent = [];

        // 2. Перебираем настройки задания, чтобы понять, что сохранять
        // (Мы доверяем настройкам из БД, а не тому, что прислал юзер)
        foreach ($homework->submission_fields as $field) {
            $label = $field['label'];
            $type = $field['type'];
            
            // Ключ в запросе (Inertia отправляет formData вида fields[Название поля])
            // Но PHP видит точки в ключах как вложенность, поэтому берем аккуратно.
            // Laravel автоматически преобразует 'fields[Label]' в массив.
            
            // А. Обработка ФАЙЛОВ
            if ($type === 'file') {
                // Проверяем, есть ли файл в запросе по ключу "fields.Название"
                if ($request->hasFile("fields.$label")) {
                    $file = $request->file("fields.$label");
                    
                    // Сохраняем файл в папку 'homeworks' на диске 'public'
                    $path = $file->store('homeworks', 'public');
                    
                    $submissionContent[$label] = [
                        'type' => 'file',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            } 
            // Б. Обработка ТЕКСТА и ССЫЛОК
            else {
                // Просто берем текст из inputData
                $submissionContent[$label] = $inputData[$label] ?? null;
            }
        }

        // 3. Сохраняем (или обновляем) запись в БД
        $submission = HomeworkSubmission::updateOrCreate(
            [
                'homework_id' => $homework->id,
                'student_id' => Auth::id(),
            ],
            [
                'content' => $submissionContent,
                'status' => 'pending',
                'created_at' => now(),
            ]
        );

        // --- ДОБАВЛЯЕМ ОТПРАВКУ ---
        // Ищем админа (в будущем здесь будет логика поиска куратора курса)
        $admin = \App\Models\User::find(1); 
        if ($admin) {
            $admin->notify(new \App\Notifications\NewSubmissionCreated($submission));
        }
        // --------------------------

        // 4. Возвращаем назад с успехом
        return redirect()->back()->with('success', 'Ответ успешно отправлен!');
    }
}