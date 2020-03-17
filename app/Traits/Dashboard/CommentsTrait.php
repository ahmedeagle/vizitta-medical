<?php

namespace App\Traits\Dashboard;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Reservation;
use App\Models\Ticket;
use Freshbitsweb\Laratables\Laratables;

trait CommentsTrait
{
    public function getCommentById($id)
    {
        return Ticket::find($id);
    }

    public function getAllComments()
    {
        return Laratables::recordsOf(Reservation::class, function ($query) {
            return $query -> whereNotNull('user_id') ->  whereNotNull('rate_comment') -> where('rate_comment','!=','') -> select('*');
        });
    }

    public function getAllReports()
    {
        return Laratables::recordsOf(CommentReport::class);
    }


    public function getAllProviderComments()
    {
        return Laratables::recordsOf(Ticket::class, function ($query) {
            return $query->where('actor_type', 1);
        });
    }

    public function getCommentReplies($id)
    {
        return Ticket::where('ticket_id', $id)->orderBy('created_at')->orderBy('order')->get();
    }

    public function getLastReplyOrder($id)
    {
        $Comment = Comment::where('Comment_id', $id)->orderBy('order', 'DESC')->first();
        return $Comment != null ? $Comment->order : 0;
    }

}
