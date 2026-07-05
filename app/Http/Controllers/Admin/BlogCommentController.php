<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBlogCommentRequest;
use App\Interfaces\BlogCommentRepositoryInterface;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Services\BlogCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCommentController extends Controller
{
    public function __construct(
        private BlogCommentRepositoryInterface $comments,
        private BlogCommentService $commentService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogComment::class);

        $filters = $request->only(['q', 'post', 'status']);

        return view('admin.blog-comments.index', [
            'comments' => $this->comments->adminSearch($filters, 20),
            'posts' => BlogPost::orderBy('title')->pluck('title', 'id'),
            'statuses' => CommentStatus::options(),
            'statusCounts' => $this->comments->countsByStatus(),
            'filters' => $filters,
        ]);
    }

    public function update(UpdateBlogCommentRequest $request, BlogComment $blogComment): RedirectResponse
    {
        $this->authorize('update', $blogComment);

        $this->commentService->updateStatus($blogComment, $request->validated('status'));

        return back()->with('status', 'Comment '.$request->validated('status').'.');
    }

    public function destroy(BlogComment $blogComment): RedirectResponse
    {
        $this->authorize('delete', $blogComment);

        $this->commentService->delete($blogComment);

        return back()->with('status', 'Comment deleted.');
    }
}
