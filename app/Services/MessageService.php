<?php
/**
 * Created by PhpStorm.
 * User: yangtao
 * Date: 2017/10/18
 * Time: 15:51
 */
namespace App\Services;

use App\Helpers\QueryHelper;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessageService
{
    /**
     * 获取消息列表
     * @param $userId
     * @return array
     */
    public function getMessages($userId){
        //用户注册时间
        $userRegTime = User::where("id", $userId)->select("reg_time")->pluck("reg_time")->first();

        $query = Message::query()->from((new Message())->getTable().' as msg')
            ->leftJoin((new UserMessage())->getTable().' as umsg', function($join) use($userId){
                $join->on("msg.id", "=", "umsg.message_id");
                $join->on("umsg.user_id", "=", DB::raw($userId));
            })
            ->select(["msg.id", "msg.title", "msg.create_time", DB::raw("ifnull(umsg.is_read, 0) as is_read")]);

        //消息发送时间大于用户注册时间
        $query->where("msg.create_time", ">=", $userRegTime);

        //属于用户的消息
        $query->where(function($query) use($userId){
            $query->where('msg.to_user_id', $userId);
            $query->orWhere('msg.to_user_id', 0);
        });
        $query->where(function($query) use($userId){
            $query->where('umsg.user_id', $userId);
            $query->orWhereNull('umsg.user_id');
        });

        //系统未删除
        $query->where("msg.is_delete", 0);
        //用户未删除
        $query->whereNull("umsg.delete_time");

        $query->orderBy("msg.create_time", "desc");

        $messages = (new QueryHelper())->pagination($query)->get();

        return $messages;
    }

    /**
     * 阅读消息详情
     * 查询消息内容，如果消息不存在或不属于当前用户，则统一返回消息不存在
     * @param $userId
     * @param $messageId
     * @return array
     */
    public function detail($userId, $messageId){
        $message = $this->getUserMessage($userId, $messageId);
        $this->updateUserMessage($userId, $messageId, 1);
        $data = [
            'id' => $message['id'],
            'title' => $message['title'],
            'content' => $message['content'],
            'create_time' => $message['create_time'],
        ];
        return $data;
    }

    /**
     * 删除消息
     * @param $userId
     * @param $messageId
     * @return bool
     */
    public function delete($userId, $messageId){
        $message = $this->getUserMessage($userId, $messageId);
        $this->updateUserMessage($userId, $messageId, 0, 1);
        return true;
    }

    /**
     * 获取用户消息，并判断消息是否属于用户
     * @param $userId
     * @param $messageId
     * @return mixed
     * @throws \Exception
     */
    public function getUserMessage($userId, $messageId){
        $message = Message::where(['is_delete'=>0, 'id'=>$messageId])->first();
        if(!$message || ($message['type'] == Message::MSG_TYPE_PRIVATE && $message['to_user_id'] != $userId)){
            throw new \Exception("消息不存在");
        }
        return $message;
    }

    /**
     * 更新用户消息状态
     * @param int $userId 用户id
     * @param int $messageId 消息id
     * @param int $isRead 是否已读
     * @param int $isDelete 是否删除
     */
    public function updateUserMessage($userId, $messageId, $isRead=0, $isDelete = 0){
        $userMessage = UserMessage::where(['message_id'=>$messageId, 'user_id'=>$userId])->first();
        if(!$userMessage){
            $userMessage = new UserMessage();
        }

        $time = Carbon::now();
        //是否需要保存
        $isSave = false;

        if($isDelete && $userMessage['is_delete'] == 0){
            $userMessage['is_delete'] = 1;
            $userMessage['delete_time'] = $time;
            $isSave = true;
        }

        if($isRead && $userMessage['is_read'] == 0){
            $userMessage['is_read'] = 1;
            $userMessage['read_time'] = $time;
            $isSave = true;
        }

        if($isSave){
            $userMessage['message_id'] = $messageId;
            $userMessage['user_id'] = $userId;
            return $userMessage->save();
        }

        return false;
    }

    /**
     * 未读消息数量
     * @param $userId
     */
    public function unReadNum($userId){
        //用户注册时间
        $userRegTime = User::where("id", $userId)->select("reg_time")->pluck("reg_time")->first();

        $query = Message::query()->from((new Message())->getTable().' as msg')
            ->leftJoin((new UserMessage())->getTable().' as umsg', function($join) use($userId){
                $join->on("msg.id", "=", "umsg.message_id");
                $join->on("umsg.user_id", "=", DB::raw($userId));
            });

        //消息发送时间大于用户注册时间
        $query->where("msg.create_time", ">=", $userRegTime);

        //属于用户的消息
        $query->where(function($query) use($userId){
            $query->where('msg.to_user_id', $userId);
            $query->orWhere('msg.to_user_id', 0);
        });

        //系统未删除
        $query->where("msg.is_delete", 0);
        //用户未读未删除
        $query->whereNull("umsg.user_id");

        return ['un_read'=> $query->count()];
    }
}
