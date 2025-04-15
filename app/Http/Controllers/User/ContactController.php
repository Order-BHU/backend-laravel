<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Services\BrevoMailer;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submitContact(Request $request, BrevoMailer $brevo)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $user = $request->user();

      

        // Create contact record
        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $request->subject,
            'message' => $request->message
        ]);

        $details = [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $request->subject,
            'message' => $request->message
        ];

        $htmlContent = view('emails.user.contact', $details)->render();

        $brevo->sendMail(
            $user->email,          
            'Support Team',             
            $request->subject,                  
            $htmlContent,              
            config("mail.from.address", "support@bhuorder.com.ng"),  // from email
            'Order Support'             // from name
        );
     


        return response()->json([
            'message' => 'Your message has been sent successfully',
            'contact_id' => $contact->id
        ], 200);
    }

    public function updateStatus(Request $request, $contactId)
    {

        $user = $request->user();

        if ($user->account_type != 'admin') {
            return response()->json([
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:unattended,sorted'
        ]);

        $contact = Contact::findOrFail($contactId);
        $contact->status = $request->status;
        $contact->save();

        return response()->json([
            'message' => 'Contact status updated successfully',
            'status' => $contact->status
        ], 200);
    }

    public function getContactList(Request $request)
    {
        $user = $request->user();

        if ($user->account_type != 'admin') {
            return response()->json([
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $request->validate([
            'status' => 'nullable|in:unattended,sorted'
        ]);

        $query = Contact::query();

        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get contacts with pagination
        $contacts = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'contacts' => $contacts,
            'message' => 'Contact list retrieved successfully'
        ], 200);
    }
}
