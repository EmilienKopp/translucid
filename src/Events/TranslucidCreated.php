<?php

namespace Splitstack\Translucid\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Splitstack\Translucid\Concerns\ResolvesTranslucidPayload;
use Splitstack\Translucid\Translucid;

class TranslucidCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, ResolvesTranslucidPayload, SerializesModels;

    public Model|array $model;

    public function __construct(
        Model|array $model,
        public ?string $channel = null,
        public ?string $table = null,
        public ?string $modelClass = null,
        public ?string $keyColumn = null,
    ) {
        $this->model = $model;
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
            'op' => 'created',
            'data' => is_array($this->model) ? $this->model : $this->model->toArray(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'translucid.created.'.$this->resolveTableName().'.'.$this->resolveKey();
    }
}
