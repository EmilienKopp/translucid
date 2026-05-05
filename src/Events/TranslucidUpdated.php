<?php

namespace Splitstack\Translucid\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Splitstack\Translucid\Concerns\ResolvesTranslucidPayload;
use Splitstack\Translucid\Translucid;

class TranslucidUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, ResolvesTranslucidPayload, SerializesModels;

    public Model|array $model;

    /** @var array<string, mixed> */
    public array $changes = [];

    public function __construct(
        Model|array $model,
        public ?string $channel = null,
        public ?string $table = null,
        public ?string $modelClass = null,
        public ?string $keyColumn = null,
    ) {
        $this->model = $model;
        $this->changes = is_array($model) ? $model : $model->getChanges();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel ?? Translucid::resolveChannel())];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->resolveTableName(),
            'model' => $this->resolveModelClass(),
            'id' => $this->resolveKey(),
            'op' => 'updated',
            'changes' => $this->changes,
        ];
    }

    public function broadcastAs(): string
    {
        return 'translucid.updated.'.$this->resolveTableName().'.'.$this->resolveKey();
    }
}
