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
            'nse_symbol' => $this['nse_symbol'],
            'bse_symbol' => $this['bse_symbol'],
            'industry' => $this['industry'],
            'market_cap' => $this['market_cap'],
            'market_cap_category' => $this['market_cap_category'],
            'listing_date' => $this['listing_date'],
            'face_value' => $this['face_value'],
            'current_price' => $this['current_price'],
            'last_updated' => $this['last_updated'],
            'metrics' => $this['metrics'],
        ];
    }
}
