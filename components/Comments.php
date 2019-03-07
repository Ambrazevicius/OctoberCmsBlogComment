<?php namespace Tallpro\BlogComments\Components;

use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Request;
use Flash;
use ValidationException;
use ApplicationException;
use Auth;
use Tallpro\BlogComments\Models\Settings;
use Validator;
use Mail;
use RainLab\Blog\Models\Post as PostModel;
use Tallpro\BlogComments\Models\Comments as CommentsModel;

class Comments extends ComponentBase
{

    public $url;
    public $posts;
    public $count;
    public $guest;
    public $ip;

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        $this->url = mb_strtolower(Request::url());
        $this->guest = Settings::get('allow_guest');
        $this->page['user'] = Auth::getUser();
        $this->page['guest'] = $this->guest;
        $this->ip = Request::ip();
    }

    public function onRun()
    {

        $this->posts = $this->page['posts'] = $this->listPosts();
        $this->addCss('/plugins/tallpro/blogcomments/assets/main.css');
        $this->addJs('/plugins/tallpro/blogcomments/assets/js/comment.js');

    }

    protected function listPosts()
    {
        $comments = CommentsModel::where(['url' => $this->url, 'status' => CommentsModel::STATUS_APPROVED])
            ->orderBy('created_at', 'desc')->get();
        $this->count = count($comments);
        return $this->buildTree($comments);

    }

    public function buildTree($elements, $parentId = 0)
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[$element->id] = $element;
            }
        }
        return $branch;
    }

    public function componentDetails()
    {
        return [
            'name' => 'Comments',
            'description' => 'Show comments section on blog post.'
        ];
    }

    public function onComment()
    {
        $model = new CommentsModel();
        $model->comment = strip_tags(post('comment'));
        $model->url = $this->url;

        $blog_slug = $this->property('blog_slug');

        $post = PostModel::where('slug', '=', $blog_slug)->first();

        if (!$post) {
            throw new ApplicationException('tallpro.blogcomments::lang.settings.no_post');
        }

        $user = Auth::getUser();
        $data = post();

        if ($this->guest && !Auth::check()) {
            $rules = [
                'user_name' => 'required',
                'user_email' => 'required|email',
                'comment' => 'required'
            ];
        } else {
            $rules = [
                'comment' => 'required'
            ];
        }

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            throw new ValidationException($validation);
        }


        if ($this->guest && !Auth::check()) {
            $model->user_name = post('user_name');
            $model->user_email = post('user_email');
        } else {

            $model->user_id = $user->id;
        }

        $model->status = Settings::get('status', 1);
        $model->post_id = $post->id;

        if ($model->save()) {

            if ($model->status == 1) {
                return ['content' => $this->renderPartial('@list.htm', ['posts' => [$model]]),
                    'message' => 'You\'r comment is published!'];
            } else {
                return ['message' => 'Thanks for the comment! We are reviewing it now!'];
            }

        } else {
            return ['content' => $this->renderPartial('@list.htm', ['message' => 'Something went wrong... Try again.'])];

        }
    }

    public function checkPermission()
    {

        $mytime = Carbon::now();

        $post = CommentsModel::where(['ip', '=', $this->ip])->first();

        if ($post):

            $startTime = Carbon::parse($post->created_at);
            $finishTime = Carbon::parse($mytime);

            $diff = $startTime->diffInSeconds($finishTime);

            if ($diff > 10):
                return true;
            else:
                return false;
            endif;

        else:

            return true;

        endif;

    }

    public function defineProperties()
    {
        return [
            'blog_slug' => [
                'title' => 'tallpro.blogcomments::lang.settings.blog_slug',
                'description' => 'tallpro.blogcomments::lang.settings.blog_slug_comment',
                'default' => '{{ :slug }}',
                'type' => 'string',
            ]
        ];
    }
}
