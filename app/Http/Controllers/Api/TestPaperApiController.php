<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Client;
use App\Models\TestPaper;
use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class TestPaperApiController extends Controller

{
    public function TestPaperlist(Request $request)
    {
        $request->validate([
            'language' => 'required'

        ]);

        try {
            $Testpaperlist = TestPaper::where('language', $request->language)->get();


            $result = [];

            // Loop through each test paper and get the count of questions
            foreach ($Testpaperlist as $testPaper) {
                $questionCount = Question::where('TestPaper_id', $testPaper->TestPaper_id)->count();

                $result[] = [
                    'TestPaper_id' => $testPaper->TestPaper_id,
                    'TestPaper_name' => $testPaper->TestPaper_name,
                    'language' => $testPaper->language,
                    'question_count' => $questionCount
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Test paper list fetched successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function Questionlist(Request $request)
    {
        $request->validate([
            'TestPaper_id' => 'required'
        ]);

        try {
            // Fetch all questions related to the given TestPaper_id
            $Questionlist = Question::where('TestPaper_id', $request->TestPaper_id)->get();

            // Transform the collection into an array of structured data
            $formattedQuestions = $Questionlist->map(function ($question) {
                return [
                    "test_question_id" => $question->test_question_id,
                    "question" => $question->question,
                    "option_1" => $question->option_1,
                    "option_2" => $question->option_2,
                    "option_3" => $question->option_3,
                    "answer" => $question->answer,
                    "language" => $question->language,
                    "TestPaper_id" => $question->TestPaper_id,
                    "image" => $question->image ? asset('upload/questionImage/' . $question->image) : '',
                    "iStatus" => $question->iStatus,
                    "strIP" => $question->strIP,
                    "created_at" => $question->created_at,
                    "updated_at" => $question->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedQuestions,
                'message' => 'Question list fetched successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
