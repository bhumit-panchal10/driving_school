<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Auth;
use App\Models\Driver;
use App\Models\Customer;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class PushNotificationController extends Controller
{
    public function notification($request)
    {
        $title = $request['title'];
        $body = $request['body'];
        $guid = $request['guid'];
        $type = $request['type'];
        $service = $request['service'];

        if ($service == "driver") {
            $FcmToken = Driver::where("id", $request['id'])->pluck('firebaseDeviceToken')->all();
        } else {
            $FcmToken = Customer::where("customerid", $request['id'])->pluck('firebaseDeviceToken')->all();
        }

        // Loop through all FCM tokens and send notifications
        // foreach ($FcmTokens as $FcmToken) {
        $data = [
            'message' => [
                'token' => $FcmToken[0], // single token
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => [
                    'guid' => $guid,
                    'type' => $type,
                    'service' => $service,
                ]
            ],
        ];
     
        $json_data = json_encode($data);
     
        // Path to the Service Account Credentials JSON file
        // $serviceAccountPath = __DIR__ . '/../../../vybecabs-2a236-firebase-adminsdk-aqlvk-04e4a87b0d.json';
        $serviceAccountPath = __DIR__ . '/../../../vybe-cabs-f264f-firebase-adminsdk-ia7md-f0992269d6.json';
        // Initialize the Google Client
        $client = new Client();
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        try {
            $credentials = new ServiceAccountCredentials($scopes, $serviceAccountPath);
            $accessToken = $credentials->fetchAuthToken()['access_token'];
            $url = 'https://fcm.googleapis.com/v1/projects/vybe-cabs-f264f/messages:send';
            $client = new Client();
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => $json_data,
            ]);

            $result = $response->getBody()->getContents();
            // Log successful response or handle as needed
        } catch (\Exception $e) {
            // Log or handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
        // }
    }
}
