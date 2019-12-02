<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Product;
use App\User;
use Auth;

class ProductTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'created_by'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Product $product)
    {
      return [
        'id'            => (int) $product->id,
        'name'          => (string) $product->name,
        'detail'         => (string) $product->detail,
        'created_at'    => $product->created_at
      ];

    }

    public function includecreatedBy(Product $product)
    {
      $user = $product->user;

      return $this->item($user,new UserTransformer());
    }
}
