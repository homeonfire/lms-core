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
    
    // Properties for order details modal
    public ?int $selectedOrderId = null;
    public array $editingOrderData = [];
    public string $newTag = '';

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

    public function selectOrder($orderId)
    {
        $order = Order::with(['user', 'course', 'manager', 'tariff'])->find($orderId);
        if ($order) {
            $this->selectedOrderId = $orderId;
            $this->editingOrderData = [
                'amount' => $order->amount / 100,
                'manager_id' => $order->manager_id,
                'funnel_stage_id' => $order->funnel_stage_id,
                'tags' => $order->tags ?? [],
            ];
            $this->newTag = '';
        }
    }

    public function closeOrder()
    {
        $this->selectedOrderId = null;
        $this->editingOrderData = [];
        $this->newTag = '';
    }

    public function saveOrderDetails()
    {
        $order = Order::find($this->selectedOrderId);
        if ($order) {
            $order->update([
                'amount' => (int) ($this->editingOrderData['amount'] * 100),
                'manager_id' => $this->editingOrderData['manager_id'] ?: null,
                'funnel_stage_id' => $this->editingOrderData['funnel_stage_id'],
                'tags' => $this->editingOrderData['tags'],
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Детали заказа сохранены')
                ->success()
                ->send();

            $this->closeOrder();
        }
    }

    public function addTag()
    {
        $tag = trim($this->newTag);
        if ($tag !== '') {
            $tags = $this->editingOrderData['tags'] ?? [];
            if (!in_array($tag, $tags)) {
                $tags[] = $tag;
                $this->editingOrderData['tags'] = $tags;
                
                if ($this->selectedOrderId) {
                    $order = Order::find($this->selectedOrderId);
                    if ($order) {
                        $order->update(['tags' => $tags]);
                    }
                }
            }
            $this->newTag = '';
        }
    }

    public function removeTag($tagToRemove)
    {
        $tags = $this->editingOrderData['tags'] ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tagToRemove));
        $this->editingOrderData['tags'] = $tags;
        
        if ($this->selectedOrderId) {
            $order = Order::find($this->selectedOrderId);
            if ($order) {
                $order->update(['tags' => $tags]);
            }
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

            // If the modal for this order is open, sync the stage
            if ($this->selectedOrderId == $orderId) {
                $this->editingOrderData['funnel_stage_id'] = $stageId;
            }

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
        $managers = \App\Models\User::role(['Super Admin', 'Manager'])->orderBy('name')->get();

        if ($this->funnelId) {
            $stages = FunnelStage::where('funnel_id', $this->funnelId)
                ->orderBy('sort_order')
                ->with(['orders' => function ($q) {
                    $q->with(['user', 'course', 'manager', 'tariff'])->orderBy('updated_at', 'desc');
                }])
                ->get();
        }

        $selectedOrder = null;
        if ($this->selectedOrderId) {
            $selectedOrder = Order::with(['user', 'course', 'manager', 'tariff'])->find($this->selectedOrderId);
        }

        return [
            'funnels' => $funnels,
            'stages' => $stages,
            'managers' => $managers,
            'selectedOrder' => $selectedOrder,
        ];
    }
}
