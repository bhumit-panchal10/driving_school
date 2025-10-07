<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\ContactUs;
use App\Models\Driver;
use App\Models\DriverDetail;
use App\Models\FaqMaster;
use App\Models\CareerMaster;
use App\Models\NewsUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Testimonial;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Models\NewsLetters;
use App\Models\CMSMaster;
use App\Models\MetaData;
use App\Models\OurTeam;
use App\Models\Investor;
use App\Models\PassMaster;
use App\Models\SupportInquiry;
use App\Models\Vendor;

use App\Mail\MyTestEmail;



class ApiController extends Controller
{
    public function testimonials(Request $request)
    {

        
        try {
            $testimonials = Testimonial::select(
                "id",
                "firstName",
                "lastName",
                "imageURL",
                "description",
                "rating",
                "cityName"
            )->get();

            return response()->json([
                'message' => 'successfully testimonials fetched...',
                'success' => true,
                'data' => $testimonials,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function faqs(Request $request)
    {
        try {
            $FAQs = FaqMaster::select(
                "id",
                "question",
                "answer",
                "category",
                "isActive"
            )->get();

            return response()->json([
                'message' => 'successfully FAQs fetched...',
                'success' => true,
                'data' => $FAQs,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function categories(Request $request)
    {
        try {
            $categories = CareerMaster::select(
                "id",
                "job_title",
                "imageURL",
                "slugName"
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

    public function news_updates(Request $request)
    {
        try {
            $news_updates = NewsUpdate::get();

            return response()->json([
                'message' => 'successfully news_updates fetched...',
                'success' => true,
                'data' => $news_updates,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function career(Request $request)
    {
        try {
            $request->validate(
                [
                    'name' => 'required',
                    'email' => 'required|email',
                    'number' => 'required|digits:10',
                    'gender' => 'required',
                    'resume_cv' => 'required',
                    'current_location' => 'required',
                    'jobId' => 'required',
                    'additional_information' => 'required',
                    'prior_experience' => 'required'
                ]
            );

            $img = "";
            if ($request->hasFile('resume_cv')) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $image = $request->file('resume_cv');
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationpath = $root . '/vybecab/upload/resume/';
                if (!file_exists($destinationpath)) {
                    mkdir($destinationpath, 0755, true);
                }
                $image->move($destinationpath, $img);
            }

            $data = array(
                "name" => $request->name,
                "email" => $request->email,
                "number" => $request->number,
                "gender" => $request->gender,
                "current_location" => $request->current_location,
                "jobId" => $request->jobId ?? 0,
                'resume_cv' => $img,
                "prior_experience" => $request->prior_experience ?? 0,
                "reference_person_name" => $request->reference_person_name ,
                "reference_person_email" => $request->reference_person_email,
                "additional_information" => $request->additional_information,
                'created_at' => now(),
                'strIP' => $request->ip(),
            );
            Career::create($data);

            return response()->json([
                'message' => 'SUCCESSFULLY SUBMITTED!'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function contactus(Request $request)
    {
        // try {

            $request->validate(
                [
                    'firstName' => 'required',
                    'lastName' => 'required',
                    'email' => 'required|email',
                    'phone_number' => 'required|digits:10',
                    'address' => 'required',
                    'message' => 'required'
                ]
            );

            $data = ContactUs::create([
                "firstName" => $request->firstName,
                "lastName" => $request->lastName,
                "email" => $request->email,
                "phone_number" => $request->phone_number,
                "address" => $request->address,
                "message" => $request->message,
                'created_at' => now(),
            ]);

             $SendEmailDetails = DB::table('sendemaildetails')
                 ->where(['id' => 8])
                 ->first();
                 
            $root = $_SERVER['DOCUMENT_ROOT'];
            $file = file_get_contents($root . '/mailers/contactmail.html', 'r');

            $file = str_replace('#name', $data['firstName'] . ' ' . $data['lastName'], $file);
            $file = str_replace('#email', $data['email'], $file);
            $file = str_replace('#mobile', $data['phone_number'], $file);
            $file = str_replace('#message', $data['message'], $file);
            $file = str_replace('#strEntryDate', date('d-m-Y'), $file);

            $toMail = "dev5.apolloinfotech@gmail.com";
            $strFromMail = $SendEmailDetails->strFromMail;

            $to = $toMail;
            $subject = "Contact Inquiry";
            $message = $file;
            $header = "From:" . $strFromMail . "\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-type: text/html\r\n";
            // dd($message);
            $retval = mail($to, $subject, $message, $header);     

            //====================================================================================
            // $msg = array(
            //     'FromMail' => $SendEmailDetails->strFromMail,
            //     'Title' => $SendEmailDetails->strTitle,
            //      'ToEmail' => "dev2.apolloinfotech@gmail.com",
            //     //'ToEmail' => "lipsa@vybecabs.com",
            //     'Subject' => $SendEmailDetails->strSubject
            // );

            // $mail = Mail::send('emails.contactusmail', ['data' => $data], function ($message) use ($msg) {
            //     $message->from($msg['FromMail'], $msg['Title']);
            //     $message->to($msg['ToEmail'])->subject($msg['Subject']);
            // });
            //====================================================================================
            
            // Mail::to('dev2.apolloinfotech@gmail.com')->send(new MyTestEmail([
            //     'title' => 'The Title',
            //     'body' => 'The Body',
            // ]));

            return response()->json([
                'message' => 'successfully submitted',
                'success' => true,
            ], 200);
        // } catch (ValidationException $e) {
        //     return response()->json(['errors' => $e->errors()], 422);
        // } catch (\Throwable $th) {
        //     // If there's an error, rollback any database transactions and return an error response.
        //     DB::rollBack();
        //     return response()->json(['error' => $th->getMessage()], 500);
        // }
    }
    
    public function investor(Request $request)
    {
        try {
            $request->validate(
                [
                    'strFullName' => 'required',
                    'strEmail' => 'required|email',
                    'iMobile' => 'required|digits:10',
                    'strDescription' => 'required'
                ]
            );

            $data = Investor::create([
                "strFullName" => $request->strFullName,
                "strEmail" => $request->strEmail,
                "iMobile" => $request->iMobile,
                "iAlternativeMobile" => $request->iAlternativeMobile ?? "",
                "strDescription" => $request->strDescription
            ]);

            // $SendEmailDetails = DB::table('sendemaildetails')
            //     ->where(['id' => 8])
            //     ->first();

            // $msg = array(
            //     'FromMail' => $SendEmailDetails->strFromMail,
            //     'Title' => $SendEmailDetails->strTitle,
            //     // 'ToEmail' => $request->email,
            //     'ToEmail' => "dev1.apolloinfotech@gmail.com",
            //     //'ToEmail' => "lipsa@vybecabs.com",
            //     'Subject' => $SendEmailDetails->strSubject
            // );

            // $mail = Mail::send('emails.investormail', ['data' => $data], function ($message) use ($msg) {
            //     $message->from($msg['FromMail'], $msg['Title']);
            //     $message->to($msg['ToEmail'])->subject($msg['Subject']);
            // });

            return response()->json([
                'message' => 'successfully submitted',
                'success' => true,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function job_details_page(Request $request, $id)
    {
        try {
            $CareerMaster = CareerMaster::select('id', 'job_title')->where(['id' => $id])->first();

            $foundContent = CareerMaster::where(['id' => $id])->first();
            
            $data = array(
                "id" => $foundContent->id,
                "content" => $foundContent->content,
                "status" =>  $foundContent->status,
                "isDelete" => $foundContent->isDelete,
                "jobCategoryMasterId" => $foundContent->jobCategoryMasterId,
                "job-category-master" => array(
                    "id" => $CareerMaster->id,
                    "job_title" => $CareerMaster->job_title
                )
            );


            if (!$foundContent) {
                return response()->json([
                    'message' => 'Job Not Found',
                    'success' => false
                ], 404);
            }
            return response()->json([
                'message' => 'PAGE FETCHED SUCCESSFULLY...',
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function vendor_new_registration(Request $request)
    {
        // dd($request);
        try {

            $request->validate([
                "vendorname" => 'required',
                "vendormobile" => 'required|digits:10|unique:vendor,vendormobile',
                "businessname" => 'required',
                "businessaddress" => 'required',
                "vendoremail" => 'required|email',
                "vendorsocialpage" => 'required',
                "businesscategory" => 'required',
                "businessubcategory" => 'required'

              
            ]);

           $Vendordata = array(
                "vendorname" => $request->vendorname,
                "vendormobile" => $request->vendormobile,
                "businessname" => $request->businessname,
                "businessaddress" => $request->businessaddress,
                "vendoremail" => $request->vendoremail,
                "vendorsocialpage" => $request->vendorsocialpage,
                'businesscategory' => $request->businesscategory,
                'businessubcategory' => $request->businessubcategory,
                'strIP' => $request->ip(),
            );
            $Vendor = Vendor::create($Vendordata);
            return response()->json([
                'success' => true,
                'message' => 'Registration Successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function mobile_validate(Request $request)
    {
        try {

            $Driver = Driver::where(['contactNumber' => $request->contactNumber])->first();

            if ($Driver) {
                return response()->json([
                    'success' => false,
                    'message' => "mobile already exists !",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "no mobile match"
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function newsletter(Request $request, $stateid)
    {
        try {

            $request->validate(
                [
                    'email' => 'required|email'
                ]
            );

            $data =  NewsLetters::where(['iStatus' => 1, 'isDelete' => 0, 'strEmail' => $request->email])
                ->first();

            if (!$data) {
                NewsLetters::craete([
                    'strEmail' => $request->email
                ]);
                return response()->json([
                    'success' => true,
                    'message' => "successfully submitted"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "already exists"
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function cms(Request $request)
    {
        try {
            
            $request->validate(
                [
                    'id' => 'required'
                ]
            );
            
            $data = CMSMaster::where(['id'=>$request->id])->first();

            return response()->json([
                'message' => 'successfully data fetched...',
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function seodata(Request $request)
    {
        try {
            
            $request->validate(
                [
                    'id' => 'required'
                ]
            );
            
            $MetaData = MetaData::where(['id' => $request->id ])
                ->first();
    
            if (!empty($MetaData)) {
                
                $data = array(
                    'id' => $MetaData->id,
                    'pagename' => $MetaData->pagename,
                    'metaTitle' => $MetaData->metaTitle,
                    'metaKeyword' => $MetaData->metaKeyword,
                    'metaDescription' => $MetaData->metaDescription,
                    'head' => $MetaData->head,
                    'body' => $MetaData->body
                );
                
                return response()->json([
                    'message' => 'successfully data fetched...',
                    'success' => true,
                    'data' => $data,
                ], 200);
            
            } 
            
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function ourTeam(Request $request)
    {
        try {
            $ourTeams =  OurTeam::where(['iStatus' => 1, 'isDelete' => 0])
                ->orderBy('iTeamId', 'asc')
                ->get();
            $data = [];
            foreach ($ourTeams as $ourTeam) {
                $data[]  = array(
                    "iTeamId" => $ourTeam->iTeamId,
                    "strName" => $ourTeam->strName,
                    "strDesignation" => $ourTeam->strDesignation,
                    "strDescription" => $ourTeam->strDescription,
                    "strFacebookUrl" => $ourTeam->strFacebookUrl,
                    "strXUrl" => $ourTeam->strXUrl,
                    "strInstagramUrl" => $ourTeam->strInstagramUrl,
                    "strLinkedInUrl" => $ourTeam->strLinkedInUrl,
                    "strImage" => "https://vybecabs.com/vybecab/upload/ourteam/" . $ourTeam->strImage
                    // "strImage" => $ourTeam->strImage
                );
            }
            if (!empty($data)) {
                return response()->json([
                    'success' => true,
                    'message' => "List Found.",
                    "List" => $data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Not List Found."
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function all_passes(Request $request)
    {
        try {
            $passes = PassMaster::get();

            return response()->json([
                'message' => 'successfully passes fetched...',
                'success' => true,
                'data' => $passes,
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function pass_detail(Request $request)
    {
        try {
            
            $request->validate([
                "passid" => "required"
            ]);
            
            $PassMaster = PassMaster::where(['id' => $request->passid])->first();

            $data = array(
                "id" => $PassMaster->id,
                "iPassType" => $PassMaster->iPassType,
                "strTitle" =>  $PassMaster->strTitle,
                "strSlug" => $PassMaster->strSlug,
                "iPrice" => $PassMaster->iPrice,
                "iDiscount" => $PassMaster->iDiscount,
                "strCoverage" => $PassMaster->strCoverage,
                "strAdditionalCharges" => $PassMaster->strAdditionalCharges,
                "strDays" => $PassMaster->strDays,
                "strEligibility" => $PassMaster->strEligibility,
                "strDescription" => $PassMaster->strDescription,
                "strTimePeriod" => $PassMaster->strTimePeriod,
            );


            if (!$data) {
                return response()->json([
                    'message' => 'pass not found',
                    'success' => false
                ], 404);
            }
            return response()->json([
                'message' => 'DATA FETCHED SUCCESSFULLY...',
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function support(Request $request)
    {
        try {
            $request->validate(
                [
                    'strTicketType' => 'required',
                    'strSubject' => 'required',
                    'strDescription' => 'required'
                ]
            );

            SupportInquiry::create([
                "strTicketType" => $request->strTicketType,
                "strSubject" => $request->strSubject,
                "strDescription" => $request->strDescription,
                'created_at' => now(),
                'strIP' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'successfully submitted',
                'success' => true,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // If there's an error, rollback any database transactions and return an error response.
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
