<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CommentModel;
use App\Models\User;

class ReportComment extends Model
{
    protected $table = 'report_comment';
    protected $fillable = [
        'comment_id',
        'reason',
    ];

    public function comment()
    {
        return $this->belongsTo(CommentModel::class, 'comment_id');
    }
}
