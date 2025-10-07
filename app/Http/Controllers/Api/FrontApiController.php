<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Image;
use App\Models\Deals;
use App\Models\DealsOption;
use App\Models\Vendor;
use App\Models\Dealmultiimage;
use App\Models\StateMaster;
use App\Models\CityMaster;
use App\Models\promocode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use GuzzleHttp\Client;

class FrontApiController extends Controller
{



    public function statelist(Request $request)
    {
        try {

            $listOfStates = StateMaster::select(
                "stateId",
                "stateName"
            )->orderBy('stateName', 'asc')->where(['iStatus' => 1, 'isDelete' => 0])->get();

            return response()->json([
                'success' => true,
                'message' => "successfully fetched StateList...",
                'data' => $listOfStates,
            ], 200);
        } catch (\Throwable $th) {

            // If there's an error, rollback any database transactions and return an error response.

            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function citylist(Request $request, $stateid = null)
    {
        try {

            $stateid = $request->stateid;
            if ($stateid) {
                $request->validate([
                    'stateid' => 'required|integer',
                ]);
            }
            $formattedCities =  CityMaster::select(
                "city-masters.stateMasterStateId",
                "state-masters.stateName",
                "city-masters.iStatus",
                "city-masters.cityId",
                "city-masters.cityName"
            )
                ->leftjoin('state-masters', 'city-masters.stateMasterStateId', '=', 'state-masters.stateId')
                ->get();

            if ($stateid) {


                $formattedCities = CityMaster::select(
                    "city-masters.stateMasterStateId",
                    "state-masters.stateName",
                    "city-masters.iStatus",
                    "city-masters.cityId",
                    "city-masters.cityName"
                )
                    ->where(['city-masters.iStatus' => 1, 'city-masters.stateMasterStateId' => $stateid])
                    ->leftjoin('state-masters', 'city-masters.stateMasterStateId', '=', 'state-masters.stateId')
                    ->get();

                if (!$formattedCities->count() > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "no data found",
                        'data' => $formattedCities,
                    ], 200);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Cities fetched successfully",
                'data' => $formattedCities,
            ], 200);
        } catch (ValidationException $e) {

            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {

            // If there's an error, rollback any database transactions and return an error response.

            DB::rollBack();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function categories(Request $request)
    {

        try {
            $categories = Categories::select(
                "Categories_id",
                "Category_name",
            )->get();

            return response()->json([
                'message' => 'successfully categories fetched...',
                'success' => true,
                'data' => $categories,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function getImages()
    {
        try {
            //Fetch images with their category and subcategory
            // $images = Image::with(['category', 'subcategory'])->get();
            // dd($images);

            $images = Image::select(
                'id',
                'cat_name',
                'subcat_name',
                'Image',
                'created_at'
            )->get();



            // Format the data for the API response
            $response = $images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'category' => $image->cat_name,
                    'subcategory' => $image->subcat_name,
                    'image_url' => asset('/upload/Image/' . $image->Image),
                    'created_at' => $image->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch images: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function getuploadimages(Request $request)
    {
        try {

            $request->validate([

                "Deals_id" => 'required'
            ]);



            $getmultiimages = Dealmultiimage::select(
                'Dealimage_id',
                'photo',
                'deal_id',
                'created_at'
            )
                ->where('deal_id', $request->Deals_id)
                ->get();

            // Format the data for the API response
            $response = $getmultiimages->map(function ($image) {
                return [
                    'Dealimage_id' => $image->Dealimage_id,
                    'deal_id' => $image->deal_id,
                    'photo' => $image->photo,
                    'created_at' => $image->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch images: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function DisplayLocation(Request $request)
    {
        try {

            $request->validate([

                "vendorid" => 'required'
            ]);


            $Vendor = Vendor::select('vendor.*', 'state-masters.stateName as state_name', 'city-masters.cityName as city_name')
                ->leftJoin('state-masters', 'vendor.vendorstate', '=', 'state-masters.stateId')
                ->leftJoin('city-masters', 'vendor.vendorcity', '=', 'city-masters.cityId')
                ->where('vendor.vendor_id', '=', $request->vendorid)
                ->first();




            return response()->json([
                'success' => true,
                'data' => $Vendor,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch images: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function Deallist(Request $request)
    {

        try {
            $request->validate([
                'Deals_id' => 'required|exists:Deals,Deals_id', // Ensure the deal exists
            ]);

            // Fetch the deal along with options and images
            $deal = Deals::with(['options', 'images', 'vendor'])
                ->where('Deals_id', $request->Deals_id)
                ->first();

            if ($deal) {
                return response()->json([
                    'success' => true,
                    'data' => $deal,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function ManageDeal(Request $request)
    {
        try {


            // Fetch the deal along with options and images
            $deal = Deals::get();

            if ($deal) {
                return response()->json([
                    'success' => true,
                    'data' => $deal,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function UpdateLocation(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                "vendorid" => 'required',  // Ensure vendor exists
                "vendoraddress" => 'required',
                "vendormobile" => 'required',
                "vendorstate" => 'required',
                "vendorcity" => 'required',
                "Deals_id" => 'required'
            ]);

            // Find the vendor by vendor_id
            $vendor = Vendor::find($request->vendorid);

            if ($vendor) {
                // Update the vendor's address and mobile
                $vendor->update([
                    'vendoraddress' => $request->vendoraddress,
                    'vendormobile' => $request->vendormobile,
                    'vendorstate' => $request->vendorstate,
                    'vendorcity' => $request->vendorcity,
                    'deal_id' => $request->Deals_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vendor location updated successfully.',
                    'data' => $vendor,  // Return the updated vendor data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function OptionList(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                "Deals_id" => 'required|exists:Deals,Deals_id', // Ensure the deal exists
            ]);

            // Fetch the options related to the specified deal
            $dealOptions = DealsOption::where('deal_id', $request->Deals_id)->get();

            if ($dealOptions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No options found for the specified deal.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $dealOptions,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve options: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function AddBusinessInfo(Request $request)
    {

        try {
            // Validate the incoming request
            $request->validate([
                "business_desc" => 'required|string',
                "business_website" => 'required',
                "business_type" => 'required|string',
                "Deals_id" => 'required|exists:Deals,Deals_id', // Ensure Deals_id exists in the deals table
            ]);

            // Check if the deal exists
            $deal = Deals::where('Deals_id', $request->Deals_id)->first();


            if ($deal) {
                // Update the deal with new business info
                $deal->update([
                    'business_desc' => $request->business_desc,
                    'business_website' => $request->business_website,
                    'business_type' => $request->business_type,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $deal, // Return the updated deal
                    'message' => 'Deal Business Info Add Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function DealPublish(Request $request)
    {

        try {
            // Validate the incoming request
            $request->validate([

                "Deals_id" => 'required|exists:Deals,Deals_id', // Ensure Deals_id exists in the deals table
            ]);

            // Check if the deal exists
            $deal = Deals::where('Deals_id', $request->Deals_id)->first();


            if ($deal) {
                // Update the deal with new business info
                $deal->update([
                    'Is_publish' => 1,

                ]);

                return response()->json([
                    'success' => true,
                    'data' => $deal,
                    'message' => 'Deal Publish Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }



    public function Adddesortitle(Request $request)
    {
        try {
            // Validate the request data
            if ($request->has('Deals_id') && $request->Deals_id) {
                $request->validate([
                    "main_title" => 'required',
                    "deal_description" => 'required',
                    "Deals_id" => 'required',

                ]);
            } else {
                $request->validate([
                    "main_title" => 'required',
                    "deal_description" => 'required',
                    "vendorid" => 'required',


                ]);
            }

            // Check if the Deals_id is provided in the request
            if ($request->has('Deals_id') && $request->Deals_id) {
                // Check if the deal exists by Deals_id
                $deal = Deals::where('Deals_id', $request->Deals_id)->first();

                if ($deal) {
                    // If the deal exists, update it with the new data
                    $deal->update([
                        "main_title" => $request->main_title,
                        "deal_description" => $request->deal_description,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $deal,
                        'message' => 'Deal Title Updated Successfully',
                    ], 200);
                } else {
                    // If the deal does not exist, return an error
                    return response()->json([
                        'success' => false,
                        'message' => 'Deal not found',
                    ], 404);
                }
            } else {
                // If no Deals_id is provided, create a new deal
                $deal = Deals::create([
                    "main_title" => $request->main_title,
                    "deal_description" => $request->deal_description,
                    "vendorid" => $request->vendorid,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $deal,
                    'message' => 'Deal Title Added Successfully',
                ], 201);
            }
        } catch (\Throwable $th) {
            // If an error occurs, roll back the transaction and return an error response
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Addoption(Request $request)
    {
        try {
            $request->validate([
                "title" => 'required',
                "regularprice" => 'required',
                "pricecut_price" => 'required',
                "month_voucher_cap" => 'required',
                "Deals_id" => 'required' // Validate Deals_id exists in the Deals table
            ]);

            if ($request->has('Deals_id') && $request->Deals_id) {
                // Check if the deal exists
                $deal = Deals::find($request->Deals_id);

                if ($deal) {
                    // Create a new deal option and associate it with the deal
                    $dealOption = DealsOption::create([
                        'deal_id' => $request->Deals_id,  // Associate the deal option with the Deals_id
                        'option_title' => $request->title,
                        'regular_price' => $request->regularprice,
                        'pricecut_price' => $request->pricecut_price,
                        'monthly_voucher_cap' => $request->month_voucher_cap,
                        'strIP' => $request->ip(),
                        'created_at' => now()

                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $dealOption,
                        'message' => 'Deal Option Added Successfully',
                    ], 201);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Deal not found',
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deals_id is required',
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Showtitleordescription(Request $request)
    {

        try {
            // Find the deal option by DealsOption_id

            $request->validate([

                "Deals_id" => 'required'
            ]);

            $deal = Deals::select('main_title', 'deal_description')->find($request->Deals_id);

            if ($deal) {
                return response()->json([
                    'success' => true,
                    'data' => $deal,
                    'message' => 'Deal Display Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal Option not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function UpdateOption(Request $request)
    {

        try {
            // Validate the input data
            $request->validate([
                'option_title' => 'required|string',
                'regular_price' => 'required|numeric',
                'pricecut_price' => 'required|numeric',
                'monthly_voucher_cap' => 'required|numeric',
                'deal_option_id' => 'required',
            ]);

            // Find the deal option by DealsOption_id
            $dealOption = DealsOption::find($request->deal_option_id);

            if ($dealOption) {
                // Update the deal option
                $dealOption->update([
                    'option_title' => $request->option_title,
                    'regular_price' => $request->regular_price,
                    'pricecut_price' => $request->pricecut_price,
                    'monthly_voucher_cap' => $request->monthly_voucher_cap,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $dealOption,
                    'message' => 'Deal Option Updated Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal Option not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function DeleteOption(Request $request)
    {
        try {
            // Find the deal option by DealsOption_id
            $request->validate([

                "deal_option_id" => 'required'
            ]);
            $dealOption = DealsOption::find($request->deal_option_id);


            if ($dealOption) {
                // Delete the deal option
                $dealOption->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Deal Option Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal Option not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function AddPhoto(Request $request)
    {
        try {
            // Validate inputs
            $validationRules = [
                'Deals_id' => 'required',
            ];

            if ($request->has('imagebank_id')) {
                $validationRules['imagebank_id'] = 'required|exists:Image,id';
            }

            $request->validate($validationRules);

            // Fetch ImageBank if imagebank_id is provided
            $Imagebank = $request->has('imagebank_id')
                ? Image::findOrFail($request->imagebank_id)
                : null;


            // Define upload directory
            $root = $_SERVER['DOCUMENT_ROOT'];
            $currentDate = now()->format('d-m-y');
            $destinationPath = $root . '/upload/deal-images/' . $currentDate . '/';
            // dd($destinationPath);
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $imgNames = [];
            $isPrimary = 1; // Default the first image as primary

            // Handle photo uploads
            if ($request->hasFile('photos')) {
                $photos = $request->file('photos');

                foreach ($photos as $index => $image) {
                    $imgName = time() . '_' . $image->getClientOriginalName();
                    $image->move($destinationPath, $imgName);

                    $dealImage = Dealmultiimage::create([
                        'Image_type' => 0,
                        'photo' => $imgName,
                        'deal_id' => $request->Deals_id,
                        'is_primary' => $index === 0 ? 1 : 0,
                        'created_at' => now(),
                        'strIP' => $request->ip(),
                    ]);

                    $imgNames[] = $imgName;
                }
            }

            // Handle ImageBank scenario

            if ($Imagebank) {
                // Define the source and destination paths
                $sourceImagebankPath = $root . '/upload/Image/' . $Imagebank->Image; // Assuming the Imagebank images are stored here
                $destinationImagebankPath = $root . '/upload/deal-images/' . $currentDate . '/' . $Imagebank->Image;

                if (file_exists($sourceImagebankPath)) {
                    // Copy the ImageBank image to the destination directory
                    if (!file_exists($destinationImagebankPath)) {
                        copy($sourceImagebankPath, $destinationImagebankPath);
                    }

                    // Save the ImageBank image details to the database
                    Dealmultiimage::create([
                        'Image_type' => 1,
                        'photo' => $Imagebank->Image,
                        'deal_id' => $request->Deals_id,
                        'imagebank_id' => $request->imagebank_id,
                        'created_at' => now(),
                        'strIP' => $request->ip(),
                    ]);

                    // Add the image to the response URL list
                    $imgNames[] = $Imagebank->Image;
                } else {
                    // Source file does not exist
                    return response()->json([
                        'success' => false,
                        'message' => 'ImageBank file does not exist at the source location.',
                    ], 404);
                }
            }

            // Build response for uploaded photos
            $photoUrls = array_map(function ($imgName) use ($currentDate) {
                return asset('upload/deal-images/' . $currentDate . '/' . $imgName);
            }, $imgNames);

            return response()->json([
                'success' => true,
                'message' => 'Photos added successfully.',
                'photos' => $photoUrls,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', Arr::flatten($e->errors())),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update photos: ' . $th->getMessage(),
            ], 500);
        }
    }


    public function deleteImage(Request $request)
    {

        $request->validate([

            "Dealimage_id" => 'required',
        ]);
        try {
            // Find the image record in the database
            $image = Dealmultiimage::findOrFail($request->Dealimage_id);
            // Get the image path
            $imagePath = public_path('upload/deal-images/' . $image->photo);
            // Check if the image is primary
            $isPrimary = $image->is_primary;
            // Delete the image file from the filesystem
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            // Delete the record from the database
            $image->delete();
            // If the deleted image was primary, set another image as primary
            if ($isPrimary) {
                $nextImage = Dealmultiimage::where('deal_id', $image->deal_id)->first();
                if ($nextImage) {
                    $nextImage->update(['is_primary' => 1]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function promocodeadd(Request $request)
    {
        // Validate the incoming data
        // dd('hy');
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:15|unique:promocodes,code', // Ensure it's unique
            'dis_per' => 'required|numeric|min:0',
            'description' => 'required|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category_id' => 'nullable|exists:Categories,Categories_id',  // Optional category
            'subcategory_id' => 'nullable|exists:subcategory,iSubCategoryId', // Optional subcategory
            'state_id' => 'nullable',
            'city_id' => 'nullable',
            'vendorid' => 'nullable',
            'link' => 'nullable',
        ]);

        // Check for validation failure
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);  // 422 Unprocessable Entity
        }


        try {
            // Create the promocode in the database
            $promocode = new promocode([
                'code' => $request->input('code'),
                'dis_per' => $request->input('dis_per'),
                'description' => $request->input('description'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'category_id' => $request->input('category_id'),
                'subcat_id' => $request->input('subcategory_id'),
                'state_id' => $request->input('state_id'),
                'city_id' => $request->input('city_id'),
                'vendorid' => $request->input('vendorid'),
                'link' => $request->input('link'),
                'created_at' => now(),
                'strIP' => $request->ip(),
            ]);
            // dd($promocode);

            // Save to the database
            $promocode->save();

            return response()->json([
                'success' => true,
                'message' => 'Promocode created successfully.',
                'data' => $promocode
            ], 201);  // 201 Created

        } catch (\Exception $e) {
            // Handle any errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the promocode.',
            ], 500);  // 500 Internal Server Error
        }
    }

    public function promocodelist(Request $request)
    {
        try {
            // Fetch promocodes with related data
            $promocode = promocode::with(['state', 'city', 'category', 'subcategory'])->get();

            if ($promocode->isNotEmpty()) {
                // Transform the data to include related names
                $data = $promocode->map(function ($promo) {
                    return [
                        'promo_id' => $promo->promo_id,
                        'code' => $promo->code,
                        'description' => $promo->description,
                        'start_date' => $promo->start_date,
                        'end_date' => $promo->end_date,
                        'iStatus' => $promo->iStatus,
                        'isDelete' => $promo->isDelete,
                        'strIP' => $promo->strIP,
                        'created_at' => $promo->created_at,
                        'updated_at' => $promo->updated_at,
                        'link' => $promo->link,
                        'dis_per' => $promo->dis_per,
                        'state_name' => $promo->state->stateName ?? null, // Access state name
                        'city_name' => $promo->city->cityName ?? null,   // Access city name
                        'category_name' => $promo->category->Category_name ?? null, // Access category name
                        'subcategory_name' => $promo->subcategory->strSubCategoryName ?? null, // Access subcategory name
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Promocode not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'success' => false,
                    'error' => $th->getMessage(),
                ],
                500
            );
        }
    }



    public function promocodeshow(Request $request)
    {

        try {

            $request->validate([
                'promo_id' => 'required|exists:promocodes,promo_id',
            ]);
            $promocode = promocode::where('promo_id', $request->promo_id)->first();

            if ($promocode) {
                return response()->json([
                    'success' => true,
                    'data' => $promocode,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Updatepromocode(Request $request)
    {
        try {

            $request->validate([
                'code' => 'required|string|max:50|unique:promocodes,code',
                'dis_per' => 'required|numeric|min:0',
                'promo_id' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'category_id' => 'nullable|exists:Categories,Categories_id',
                'subcategory_id' => 'nullable|exists:subcategory,iSubCategoryId',
                'state_id' => 'nullable',
                'city_id' => 'nullable',
                'vendorid' => 'nullable',
                'link' => 'nullable',
            ]);

            // Find the vendor by vendor_id
            $promocode = promocode::find($request->promo_id);

            if ($promocode) {
                // Update the vendor's address and mobile
                $promocode->update([
                    'code' => $request->code,
                    'dis_per' => $request->dis_per,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'category_id' => $request->category_id,
                    'subcategory_id' => $request->subcategory_id,
                    'state_id' => $request->state_id,
                    'city_id' => $request->city_id,
                    'vendorid' => $request->vendorid,
                    'link' => $request->link,
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'promocode updated successfully.',
                    'data' => $promocode,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'promocode not found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promocode: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function Deletepromocode(Request $request)
    {
        try {
            $request->validate([

                "promo_id" => 'required'
            ]);
            $promocode = promocode::find($request->promo_id);


            if ($promocode) {
                $promocode->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'promocode Deleted Successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'promocode not found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function promocodesearch(Request $request)
    {

        $query = promocode::query();

        if ($request->has('promo_code') && !empty($request->promo_code)) {
            $query->where('code', 'like', '%' . $request->promo_code . '%');
        }

        if ($request->has('expires_at')) {
            $query->whereDate('end_date', '=', $request->expires_at);
        }

        if ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', 'like', '%' . $request->category . '%');
        }

        if ($request->has('subcategory') && !empty($request->subcategory)) {
            $query->where('subcat_id', 'like', '%' . $request->subcategory . '%');
        }

        if ($request->has('city') && !empty($request->city)) {
            $query->where('city_id', 'like', '%' . $request->city . '%');
        }

        if ($request->has('state') && !empty($request->state)) {
            $query->where('state_id', 'like', '%' . $request->state . '%');
        }

        // Get the filtered promocodes with related data
        $promoCodes = $query->with(['category', 'subcategory', 'state', 'city'])->get();

        // Format the response to include the names of related entities
        $formattedPromoCodes = $promoCodes->map(function ($promoCode) {
            return [
                'promo_id' => $promoCode->promo_id,
                'code' => $promoCode->code,
                'category_id' => $promoCode->category_id,
                'subcat_id' => $promoCode->subcat_id,
                'city_id' => $promoCode->city_id,
                'state_id' => $promoCode->state_id,
                'start_date' => $promoCode->start_date,
                'end_date' => $promoCode->end_date,
                'iStatus' => $promoCode->iStatus,
                'isDelete' => $promoCode->isDelete,
                'strIP' => $promoCode->strIP,
                'link' => $promoCode->link,
                'dis_per' => $promoCode->dis_per,
                'vendorid' => $promoCode->vendorid,
                'category_name' => $promoCode->category ? $promoCode->category->Category_name : null,
                'subcategory_name' => $promoCode->subcategory ? $promoCode->subcategory->strSubCategoryName : null,
                'state_name' => $promoCode->state ? $promoCode->state->stateName : null,
                'city_name' => $promoCode->city ? $promoCode->city->cityName : null,
            ];
        });

        // Return the formatted response
        return response()->json([
            'success' => true,
            'data' => $formattedPromoCodes
        ]);
    }

    public function Dealsearch(Request $request)
    {
        try {
            // Build the base query

            $query = Deals::join('vendor', 'Deals.vendorid', '=', 'vendor.vendor_id')
                ->whereNotNull('vendor.latitude')
                ->whereNotNull('vendor.longitude')
                ->select('Deals.*')
                ->with(['options', 'images', 'vendor']);

            // Apply filters based on request parameters
            if ($request->has('deals') && !empty($request->deals)) {
                $query->where('Deals_id', $request->deals);
            }

            if ($request->has('category') && !empty($request->category)) {
                $query->where('deal_category_id', $request->category);
            }

            if ($request->has('subcategory') && !empty($request->subcategory)) {
                $query->where('deal_sub_category_id', $request->subcategory);
            }

            if ($request->has('Title') && !empty($request->Title)) {
                $query->where('main_title', 'like', '%' . $request->Title . '%');
            }

            if ($request->has('lat') && $request->has('long')) {

                $strUserLat = $request->lat;
                $strUserLong = $request->long;
                $initialRadius = 1000; // Start radius in meters (1 km)
                $maxRadius = 10000; // Maximum radius in meters (10 km)
                $increment = 1000; // Increment radius by 1 km (1000 meters)
                $radius = $initialRadius;
                $client = new Client();
                $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
                $nearbyVendors = collect([]);




                $vendors = Vendor::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get();

                if ($vendors->isEmpty()) {
                    return response()->json(['message' => 'No vendor found'], 404);
                }

                // Use Google Maps Distance Matrix API to calculate distances

                $origins = "$strUserLat,$strUserLong";
                $destinations = $vendors->map(function ($vendor) {
                    return "{$vendor->latitude},{$vendor->longitude}";
                })->implode('|');

                $response = $client->get($url, [
                    'query' => [
                        'origins' => $origins,
                        'destinations' => $destinations,
                        'key' => "AIzaSyDJDm56GxJQyzh8fa7dmsdEA1CVPeZBno8",
                        'departure_time' => 'now', // Set to 'now' for current traffic,                            
                    ]
                ]);


                $distanceMatrix = json_decode($response->getBody()->getContents(), true);
                if ($distanceMatrix['status'] != 'OK') {
                    return response()->json(['message' => 'Error calculating distances'], 500);
                }

                $elements = $distanceMatrix['rows'][0]['elements'];
                foreach ($vendors as $index => $vendor) {
                    $vendor->distance = $elements[$index]['distance']['value'];
                    $vendor->duration_in_traffic = $elements[$index]['duration_in_traffic']['value'];
                }


                $nearbyVendors = $vendors->filter(function ($vendor) {
                    return $vendor->distance <= 10000; // 10 km in meters
                });

                if ($nearbyVendors->isEmpty()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'No vendors found within 10 km'
                    ], 404);
                }
                //driver found code end

                $sortedVendors = $nearbyVendors->sortBy('distance')->values();

                // dd($sortedVendors);
            }

            if ($request->has('state') && !empty($request->state)) {
                $query->where('deal_state_id', 'like', '%' . $request->state . '%');
            }

            if ($request->has('city') && !empty($request->city)) {
                $query->where('deal_city_id', 'like', '%' . $request->city . '%');
            }

            // Fetch the results
            $deals = $query->get();

            // Check if results exist
            if ($deals->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => $deals,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No deals found',
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
