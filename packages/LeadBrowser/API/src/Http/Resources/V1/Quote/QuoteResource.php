<?php

namespace LeadBrowser\API\Http\Resources\V1\Quote;

use Illuminate\Http\Resources\Json\JsonResource;
use LeadBrowser\API\Http\Resources\V1\Organization\EmployeeResource;
use LeadBrowser\API\Http\Resources\V1\Setting\UserResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'description'       => $this->description,
            'expired_at'        => $this->expired_at,
            'subject'           => $this->subject,
            'employee'            => new EmployeeResource($this->employee),
            'user'              => new UserResource($this->user),
            'billing_address'   => $this->billing_address,
            'shipping_address'  => $this->shipping_address,
            'sub_total'         => $this->sub_total,
            'discount_amount'   => $this->discount_amount,
            'tax_amount'        => $this->tax_amount,
            'adjustment_amount' => $this->adjustment_amount,
            'grand_total'       => $this->grand_total,
            'updated_at'        => $this->updated_at,
            'created_at'        => $this->created_at,
        ];
    }
}
