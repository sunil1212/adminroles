<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Transformers\ProductTransformer;
// use App\Transformers\IlluminatePaginatorAdapter;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\ArraySerializer;
use App\Http\Controllers\ApiController;

class ProductController extends ApiController
{
  /**
* Display a listing of the resource.
*
* @return \Illuminate\Http\Response
*/
function __construct()
{
   $this->middleware('permission:product-list|product-create|product-edit|product-delete', ['only' => ['index','show']]);
   $this->middleware('permission:product-create', ['only' => ['create','store']]);
   $this->middleware('permission:product-edit', ['only' => ['edit','update']]);
   $this->middleware('permission:product-delete', ['only' => ['destroy']]);
}
/**
* Display a listing of the resource.
*
* @return \Illuminate\Http\Response
*/
public function index()
{
  //normal collection call
  // $products = Product::latest()->paginate(5);
  // return view('products.index',compact('products'))
  //     ->with('i', (request()->input('page', 1) - 1) * 5);

  //new code with transformer
  // $products = Product::all();
  //
  // $products = fractal($products, new ProductTransformer())->toArray();
  //
  // return response()->json($products);

  //new code with transformer and pagination
  $paginator = Product::paginate();
  $products = $paginator->getCollection();

  $productsresponse = fractal()
              ->collection($products, new ProductTransformer())
              ->serializeWith(new ArraySerializer)
              ->paginateWith(new IlluminatePaginatorAdapter($paginator))
              ->toArray();

              $response = $this->convertPaginationResponse($productsresponse);

              return $this->setMessage("Products fetched successfully.")
                  ->respondWithStatus($response);



}


/**
* Show the form for creating a new resource.
*
* @return \Illuminate\Http\Response
*/
public function create()
{
  return view('products.create');
}


/**
* Store a newly created resource in storage.
*
* @param  \Illuminate\Http\Request  $request
* @return \Illuminate\Http\Response
*/
public function store(Request $request)
{
  request()->validate([
      'name' => 'required',
      'detail' => 'required',
  ]);


  Product::create($request->all());

  //
  // return redirect()->route('products.index')
  //                 ->with('success','Product created successfully.');
  return "success";
}


/**
* Display the specified resource.
*
* @param  \App\Product  $product
* @return \Illuminate\Http\Response
*/
public function show(Product $product)
{
  return view('products.show',compact('product'));
}


/**
* Show the form for editing the specified resource.
*
* @param  \App\Product  $product
* @return \Illuminate\Http\Response
*/
public function edit(Product $product)
{
  return view('products.edit',compact('product'));
}


/**
* Update the specified resource in storage.
*
* @param  \Illuminate\Http\Request  $request
* @param  \App\Product  $product
* @return \Illuminate\Http\Response
*/
public function update(Request $request, Product $product)
{
   request()->validate([
      'name' => 'required',
      'detail' => 'required',
  ]);


  $product->update($request->all());


  return redirect()->route('products.index')
                  ->with('success','Product updated successfully');
}


/**
* Remove the specified resource from storage.
*
* @param  \App\Product  $product
* @return \Illuminate\Http\Response
*/
public function destroy(Product $product)
{
  $product->delete();


  return redirect()->route('products.index')
                  ->with('success','Product deleted successfully');
}

}
