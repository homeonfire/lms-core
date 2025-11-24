<?php

namespace App\Filament\Resources\HomeworkSubmissionResource\Pages;

use App\Filament\Resources\HomeworkSubmissionResource;
use App\Notifications\HomeworkGraded;
use Filament\Resources\Pages\EditRecord;

class EditHomeworkSubmission extends EditRecord
{
    protected static string $resource = HomeworkSubmissionResource::class;

    // Этот метод запускается ПОСЛЕ того, как админ нажал "Сохранить"
    protected function afterSave(): void
    {
        $record = $this->getRecord(); // Получаем обновленную запись

        // Если статус не "pending" (то есть проверено), шлем письмо студенту
        if ($record->status !== 'pending') {
            $record->student->notify(new HomeworkGraded($record));
        }
    }
}