<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EquityPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'isin' => $this['isin'],
            'name' => $this['name'],
            'current_price' => $this['current_price'],
            'last_updated' => $this['last_updated'],
            'metrics' => $this['metrics'],
        ];
    }
}
