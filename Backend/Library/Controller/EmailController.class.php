<?php
/**
 * Created by PhpStorm.
 * User: Tinywan
 * Date: 2017/1/5
 * Time: 15:29
 * Mail: Overcome.wan@Gmail.com
 */
namespace Library\Controller;

use Think\Controller;

class EmailController extends Controller
{
    /**
     * 发送邮件
     */
    public function register()
    {
        $usernName = I('post.userName', 'Guest1722318623');
        $passWord = I('post.passWord', '123456' . rand(1000, 9999));
        $email = I('post.email', '1722318623@qq.com');
        $reTime = time();
        $accessToken = md5($usernName . $passWord . $reTime);//创建用于激活识别码
        $accessExpires = $reTime + 60 * 60 * 24; //过期时间为24小时后
        $model = M('User');
        $data['username'] = $usernName;
        $data['password'] = $passWord;
        $data['nickName'] = $usernName;
        $data['accessToken'] = $accessToken;
        $data['accessExpires'] = $accessExpires;
        $data['reTime'] = $reTime;
        $data['email'] = $email;
        $data['enabled'] = 0;
        if ($model->add($data) == false) return 'error';
        $link = "http://wechatu.xd107.com/home/Index/activation?accessToken={$accessToken}";
        $str = <<<html
            您好！<p></p>
            感谢您在Tinywan世界注册帐户！<p></p>
			帐户需要激活才能使用，赶紧激活成为Tinywan家园的正式一员吧:)<p></p>
            点击下面的链接立即激活帐户(或将网址复制到浏览器中打开):<p></p>
			$link

html;
        //传递一个数组，可以实现多邮件发送
        $eamilAddress = [$email, '756684177@qq.com'];
        $result = send_email($eamilAddress, 'Tinywan世界帐户激活邮件--' . $usernName, $str);
        if ($result['error'] == 1) {
            var_dump($result);
            die;
        }
        var_dump('发送完成');
    }

    // 用户点击激活方法
    public function activation()
    {
        $accessToken = I('get.accessToken');
        $nowTime = time();
        $where['enabled'] = 0;
        $where['accessToken'] = $accessToken;
        $res = M('User')->where($where)->find();
        if (!$res) exit('该账户已被激活或者该账户不存在');
        if ($nowTime > $res['accessexpires']) exit('您的激活有效期已过，请登录您的帐号重新发送激活邮件');
        $update = M('User')->where(array('id' => $res['id']))->setField('enabled', 1);
        if ($update == false) exit('修改数据库字段失败');
        $link = "http://wechatu.xd107.com/home/Index/qrcode?accessToken={$accessToken}"; //这里跳转到一个个人博客的二维码
        header('location:' . $link);
    }

    // 邮箱验证
    public function checkEmail()
    {
        $email = I('post.email', '756684177@qq.com');
        $model = M('User');
        $condition['email'] = $email;
        $result = $model->where($condition)->find();
        if (!$result) exit('该邮箱尚未注册');
        //同时我们为了控制URL链接的时效性
        $passwordToken = md5($result['id'] . $result['username'] . $result['password']);
        $link = "http://wechatu.xd107.com/home/Index/resetPassword/email/{$email}/passwordToken/{$passwordToken}";
        $str = "您好!{$result['username']}， 请点击下面的链接重置您的密码：<p></p>" . $link;
        $sendResult = send_email($email, "Tinywan世界重置密码", $str);
        if ($sendResult['error'] == 1) exit($result);
        //修改密码发送时间
        $passwordTime = time();
        $where['id'] = $result['id'];
        $updateResult = $model->where($where)->setField('passwordTime', $passwordTime);
        if (!$updateResult) exit('修改数据库密码发送时间失败！');
        exit($email . "系统已向您的邮箱发送了一封邮件<br/>请登录到您的邮箱及时重置您的密码！");
    }

    /**
     * 密码验证
     */
    public function resetPassword()
    {
        $email = I('get.email');
        $passwordToken = I('get.passwordToken');
        $model = M('User');
        $condition['email'] = $email;
        $result = $model->where($condition)->find();
        if (!$result) exit('error link');
        $checkToken = md5($result['id'] . $result['username'] . $result['password']);
        if ($checkToken != $passwordToken) exit('this no exit link');
        var_dump($result['passwordtime']);
        if (time() - $result['passwordtime'] > 24 * 60 * 60) exit('The link has expires');
        $link = "http://wechatu.xd107.com/home/Index/reSet?uid={$result['id']}";
        // 跳转至客户密码重置页面
        header('location:' . $link);
    }

    /**
     * 重设密码验证
     */
    public function reSet()
    {
        $uid = I('get.uid');
        $model = M('User');
        $condition['id'] = $uid;
        $result = $model->where($condition)->find();
        //若果是POST方式提交的话，就是修改密码了，重置密码就是
        if (IS_POST) {
            $newPassword = I('post.password', '11111111111111');
            $email = I('post.email', '756684177@qq.com');
            $where['email'] = ':email';
            $model->password = $newPassword;
            $model->update_time = time();
            //save方法的返回值是影响的记录数，如果返回false则表示更新出错，因此一定要用恒等来判断是否更新失败
            $modPassword = $model->where($where)->bind(':email', $email)->save();
            if (!$modPassword) exit('修改密码失败');
            exit($email . 'update password success' . $modPassword);
        }
        var_dump($result);
        die;
    }

    /**
     * 验证邮件地址,
     */
    public function filterVar()
    {
        $filerRes = filter_var('756684177@qq.com', FILTER_VALIDATE_EMAIL);
        $filerRes2 = filter_var('sauron@tinywan', FILTER_VALIDATE_EMAIL);
        var_dump($filerRes);
        var_dump($filerRes2);
    }

    //通过APache 服务器方式启动一个CLi进程
    public function apacheToCli()
    {
        // echo CLI_PATH."cli.php Library/index/test";
        echo '------------------------------------启动一个CLi进程 开始--------------------------------';
        //exec("E:\wamp64\bin\php\php7.0.10\php.exe E:\wamp64\www\ThinkPhpStudy\cli.php /Library/index/cmdCliTest 2>&1", $output, $return_val);
        exec("E:\wamp64\bin\php\php7.0.10\php.exe " . CLI_PATH . "cli.php /Library/Email/cmdCliTest 2>&1", $output, $return_val);
        echo "<hr/>";
        var_dump($output);  //命令执行后生成的数组
        echo "<hr/>";
        var_dump("执行的状态:" . $return_val); //执行的状态
        echo '-----------------------------------启动一个CLi进程 结束----------------------------------';
    }

    //这个方法将被cli模式调用
    public function cmdCliTest()
    {
        sleep(10); //方便我们在任务管理器查看PHP cli进程，
        echo date("Y-m-d H:i:s", time()) . ' cmdCliTest()这个方法将被cli模式调用: ThinnPHP cli Mode Run Success:';
    }

    //cli 命令行需要执行的php文件
    public function taskTable()
    {
        $model = M("TaskList");
        $status = 0;
        $conditions = array('status' => ':status');
        $result = $model->where($conditions)->bind(':status', $status)->select();
        if (empty($result)) exit('没有可发送的邮件');
        echo '开始发送邮件:' . "\r\n";
        foreach ($result as $key => $value) {
            //发送邮件
            $result = send_email($value['user_email'], 'Tinywan激活邮件', "https://github.com/Tinywan");
            //发送成功
            if ($result['error'] == 0) {
                //修改数据库字段status 的值为1
                $model->where(array('id' => $value['id']))->setField('status', 1);
            }
            sleep(10);
            //其实在这里可以添加一个状态表示没有发送成功的标记，修改数据库字段status 的值为2
            //$model->where(array('id' => $value['id']))->setField('status', 2);
        }
        exit('发送邮件结束时间：'.date("Y-m-d H:i:s"));
    }

    //Apache 服务器需要执行的队列
    public function apacheToCliEmail()
    {
        echo '------------------------------------启动一个CLi进程 开始--------------------------------';
        exec("E:\wamp64\bin\php\php7.0.10\php.exe E:\wamp64\www\ThinkPhpStudy\cli.php /Library/Email/taskTable 2>&1", $output, $return_val);
        echo "<hr/>";
        var_dump($output);  //命令执行后生成的数组
        echo "<hr/>";
        var_dump("执行的状态:" . $return_val); //执行的状态
        echo '-----------------------------------启动一个CLi进程 结束----------------------------------';
    }
    
    //CLI模式，模拟队列的形成
    public function list(){
        $count = 0;
        while(true){
            $count++;
            echo $count."\r\n";
            if($count>10){
                break;
            }
            sleep(5);
        }
        echo "done\r\n";
        exit(1);

    }
    

}