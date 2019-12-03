<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Transformers\ProductTransformer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\ArraySerializer;
use App\Http\Controllers\ApiController;
use Auth;

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
  // $paginator = Product::paginate();
  // $products = $paginator->getCollection();
  // ->paginateWith(new IlluminatePaginatorAdapter($paginator))

  $products = Product::paginate(1);
  $response = fractal()
              ->collection($products, new ProductTransformer())
              ->serializeWith(new ArraySerializer)
              ->paginateWith(new IlluminatePaginatorAdapter($products))
              ->toArray();

              //$response = $this->convertPaginationResponse($productsresponse);

              return $this->setMessage("Products fetched successfully.")
                  ->respondWithStatus($response);



}

public function filter(Request $request, Product $product)
{
$product = $product->newQuery();

if($request->has('q')) {
  $q = $request->input('q');
  $product->orWhere('name', 'LIKE', '%' . $q . '%')
              ->orWhere('detail', 'LIKE', '%' . $q . '%');

}


// Search for a user based on their name.
if ($request->has('name')) {
  $product->where('name', $request->input('name'));
}

if($request->has('sortBy')){
    //Handle default parameter of get with second argument
    $sort = $request->input('sortBy.sort');
    $direction = $request->input('sortBy.direction');
    $product->orderBy($sort, $direction);
}

// Continue for all of the filters.

// Get the results and return them.
$products =  $product->paginate(2);

$response = fractal()
            ->collection($products, new ProductTransformer())
            ->serializeWith(new ArraySerializer)
            ->paginateWith(new IlluminatePaginatorAdapter($products))
            ->toArray();

            //$response = $this->convertPaginationResponse($productsresponse);

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

  Product::create(['name' => $request->name,
                   'detail'=>$request->detail,
                   'slug' => $this->createSlug($request->name),
                   'user_id' => Auth::id()]);


  //
  // return redirect()->route('products.index')
  //                 ->with('success','Product created successfully.');
  return "success";
}

public function createSLug($name, $id = 0)
{
  $slug = str_slug($name);
  $allSlugs = $this->getRelatedSlugs($slug, $id);
  if (! $allSlugs->contains('slug', $slug)){
    return $slug;
}

$i = 1;
$is_contain = true;
do {
    $newSlug = $slug . '-' . $i;
    if (!$allSlugs->contains('slug', $newSlug)) {
        $is_contain = false;
        return $newSlug;
    }
    $i++;
} while ($is_contain);

}

protected function getRelatedSlugs($slug, $id = 0)
{
    return Product::select('slug')->where('slug', 'like', $slug.'%')
    ->where('id', '<>', $id)
    ->get();
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
