<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\Order;
use Filament\Resources\Pages\Page;

class OrderKanban extends Page
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.order-kanban';

    protected static ?string $title = 'Канбан-доска заказов';

    public ?int $funnelId = null;

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function mount()
    {
        $activeFunnel = Funnel::where('is_active', true)->first() ?? Funnel::first();
        if ($activeFunnel) {
            $this->funnelId = $activeFunnel->id;
        }
    }

    public function updateOrderStage($orderId, $stageId)
    {
        $order = Order::find($orderId);
        $stage = FunnelStage::find($stageId);

        if ($order && $stage && $stage->funnel_id == $this->funnelId) {
            $order->update([
                'funnel_stage_id' => $stageId
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Заказ перенесен')
                ->success()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $funnels = Funnel::all();
        $stages = collect();

        if ($this->funnelId) {
            $stages = FunnelStage::where('funnel_id', $this->funnelId)
                ->orderBy('sort_order')
                ->with(['orders' => function ($q) {
                    $q->with(['user', 'course', 'manager', 'tariff'])->orderBy('updated_at', 'desc');
                }])
                ->get();
        }

        return [
            'funnels' => $funnels,
            'stages' => $stages,
        ];
    }
}
