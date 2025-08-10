<?php
return [
    //系统常用
    'lang'   => 'en',
    '网络延迟' => 'Network delay',
    '参数错误' => 'Parameter error',
    '操作频繁' => 'Frequent operation',
    '网络异常' => 'Network abnormality',
    '数据异常' => 'Data abnormality',
    '成功'    => 'Success',
    '数据不存在' => 'Data does not exist',
    '状态异常' => 'Status abnormality',
    '非法请求' => 'Illegal request',
    '系统维护' => 'system maintenance',
    
    //登录
    '请通过邀请链接注册登录' => 'Please register and log in through the invitation link',
    '请输入钱包地址' => 'Please enter the wallet address',
    '钱包地址不能为空' => 'Wallet address cannot be empty',
    '钱包地址有误'    => 'Wallet address is incorrect',
    '用户已被禁止登录' => 'User has been banned from logging in',
    '请填写邀请码'    => 'Please enter the invitation code',
    '邀请码不存在'    => 'Invitation code does not exist',
    '推荐人不存在'    => 'Referrer does not exist',
    
    //上传
    '请选择文件'    => 'Please select a file',
    '格式错误'    => 'Format error',
    '图片大小有误'    => 'Image size is incorrect',
    
    //提币
    '余额不足' => 'Insufficient balance',
    '提币数量不能为0' => 'Withdrawal amount cannot be 0',
    '提币状态1' => 'Pending review',
    '提币状态2' => 'Success',
    '提币状态3' => 'Failure',
    '每日提币次数' => 'Maximum withdrawal times per day: %s',
    'withdrawal_min' => 'Minimum withdrawal: %s coins',
    
    //PK
    '敬请期待' => 'Please stay tuned',
    
    '提币数量错误' => 'Withdrawal amount is wrong',
    '提币数量整倍数' => 'Withdrawal amount is a multiple of %s',
    '请选择倍数' => 'Please select the multiple',
    '抽奖次数不足' => 'Insufficient number of draws',
    '请选择签到价格' => 'Please select the sign-in price',
    '未到下一次签到时间' => 'The next sign-in time has not yet arrived',
    '算力不足' => 'Insufficient computing power',
    
    '只能指定伞下用户' => 'Only users under the umbrella can be specified',
    '不能超过自己级别' => "Cannot exceed one's own level",
    '此用户不可操作'  => 'This user is not operable',
    '您已经是商家' => 'You are already a merchant',
    '您不是商家' => 'You are not a merchant',
    '请选择购买数量' => 'Please select the purchase quantity',
    '请输入赠送数量' => 'Please enter the quantity of gifts to be given',
    '赠送用户不存在' => 'Gift user does not exist',
    '不能赠送给自己' => 'Cannot be gifted to oneself',
    
    '您已经是普通节点' => 'You are already a normal node',
    '您已经是超级节点' => 'You are already a super node',
    '您不满足开通条件' => 'You do not meet the conditions for opening',
    '需要算力手续费' => 'Requires computing power fee s%',
    
    //分类1系统增加2系统扣除3余额提币4提币驳回5普通节点获得6管理级奖7超级节点获得
    'USDT类型1' => 'System increase',
    'USDT类型2' => 'System deduction',
    'USDT类型3' => 'Balance withdrawal',
    'USDT类型4' => 'Withdrawal rejection',
    'USDT类型5' => 'Normal node',
    'USDT类型6' => 'Management level award',
    'USDT类型7' => 'Super node',
    
    //分类1系统增加2系统扣除3余额提币4提币驳回5签到获得6推荐加速7见点加速8团队加速9幸运抽奖
    'DOGBEE类型1' => 'System increase',
    'DOGBEE类型2' => 'System deduction',
    'DOGBEE类型3' => 'Balance withdrawal',
    'DOGBEE类型4' => 'Withdrawal rejection',
    'DOGBEE类型5' => 'Sign-in acquisition',
    'DOGBEE类型6' => 'Recommended acceleration',
    'DOGBEE类型7' => 'Meet point acceleration',
    'DOGBEE类型8' => 'Team acceleration',
    'DOGBEE类型9' => 'Lucky draw',
    
    //分类1系统增加2系统扣除3注册赠送4购买算力5签到扣除6推荐加速7见点加速8团队加速9积分兑换
    'POWER类型1' => 'System increase',
    'POWER类型2' => 'System deduction',
    'POWER类型3' => 'Balance withdrawal',
    'POWER类型4' => 'Withdrawal rejection',
    'POWER类型5' => 'Sign-in deduction',
    'POWER类型6' => 'Recommended acceleration',
    'POWER类型7' => 'Meet point acceleration',
    'POWER类型8' => 'Team acceleration',
    'POWER类型9' => 'Redeem',
    'POWER类型10' => 'Withdrawal deduction',
    'POWER类型11' => 'Withdrawal rejection',
    
    //分类1系统增加2系统扣除3开通商家4购买积分5赠送扣除6赠送获得7兑换算力
    'POINT类型1' => 'System increase',
    'POINT类型2' => 'System deduction',
    'POINT类型3' => 'Open merchant',
    'POINT类型4' => 'Purchase points',
    'POINT类型5' => 'Gift deduction',
    'POINT类型6' => 'Gift received',
    'POINT类型7' => 'Exchange computing power',
];
