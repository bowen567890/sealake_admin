<?php

/**
 * A helper file for Dcat Admin, to provide autocomplete information to your IDE
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author jqh <841324345@qq.com>
 */
namespace Dcat\Admin {
    use Illuminate\Support\Collection;

    /**
     * @property Grid\Column|Collection id
     * @property Grid\Column|Collection name
     * @property Grid\Column|Collection type
     * @property Grid\Column|Collection version
     * @property Grid\Column|Collection detail
     * @property Grid\Column|Collection created_at
     * @property Grid\Column|Collection updated_at
     * @property Grid\Column|Collection is_enabled
     * @property Grid\Column|Collection parent_id
     * @property Grid\Column|Collection order
     * @property Grid\Column|Collection icon
     * @property Grid\Column|Collection uri
     * @property Grid\Column|Collection extension
     * @property Grid\Column|Collection permission_id
     * @property Grid\Column|Collection menu_id
     * @property Grid\Column|Collection slug
     * @property Grid\Column|Collection http_method
     * @property Grid\Column|Collection http_path
     * @property Grid\Column|Collection role_id
     * @property Grid\Column|Collection user_id
     * @property Grid\Column|Collection value
     * @property Grid\Column|Collection username
     * @property Grid\Column|Collection password
     * @property Grid\Column|Collection avatar
     * @property Grid\Column|Collection remember_token
     * @property Grid\Column|Collection banner
     * @property Grid\Column|Collection vedio
     * @property Grid\Column|Collection status
     * @property Grid\Column|Collection sort
     * @property Grid\Column|Collection lang
     * @property Grid\Column|Collection bigkey
     * @property Grid\Column|Collection num
     * @property Grid\Column|Collection content
     * @property Grid\Column|Collection tab
     * @property Grid\Column|Collection key
     * @property Grid\Column|Collection help
     * @property Grid\Column|Collection element
     * @property Grid\Column|Collection rule
     * @property Grid\Column|Collection hash
     * @property Grid\Column|Collection round
     * @property Grid\Column|Collection mark
     * @property Grid\Column|Collection token
     * @property Grid\Column|Collection user
     * @property Grid\Column|Collection amount
     * @property Grid\Column|Collection time
     * @property Grid\Column|Collection uuid
     * @property Grid\Column|Collection connection
     * @property Grid\Column|Collection queue
     * @property Grid\Column|Collection payload
     * @property Grid\Column|Collection exception
     * @property Grid\Column|Collection failed_at
     * @property Grid\Column|Collection ordernum
     * @property Grid\Column|Collection team_id
     * @property Grid\Column|Collection join_usdt
     * @property Grid\Column|Collection join_ticket
     * @property Grid\Column|Collection state
     * @property Grid\Column|Collection is_win
     * @property Grid\Column|Collection not_reward
     * @property Grid\Column|Collection team_num
     * @property Grid\Column|Collection join_num
     * @property Grid\Column|Collection is_settle
     * @property Grid\Column|Collection dynamic_settle
     * @property Grid\Column|Collection kj_time
     * @property Grid\Column|Collection total_static
     * @property Grid\Column|Collection total_dynamic
     * @property Grid\Column|Collection total_sediment
     * @property Grid\Column|Collection is_gcash
     * @property Grid\Column|Collection is_tcash
     * @property Grid\Column|Collection coin_img
     * @property Grid\Column|Collection rate
     * @property Grid\Column|Collection contract_address
     * @property Grid\Column|Collection precision
     * @property Grid\Column|Collection cj_pool
     * @property Grid\Column|Collection cs_pool
     * @property Grid\Column|Collection lp_pool
     * @property Grid\Column|Collection lv
     * @property Grid\Column|Collection zhi_num
     * @property Grid\Column|Collection under_num
     * @property Grid\Column|Collection line_num
     * @property Grid\Column|Collection line_rank
     * @property Grid\Column|Collection index
     * @property Grid\Column|Collection recharge_id
     * @property Grid\Column|Collection symbol
     * @property Grid\Column|Collection price
     * @property Grid\Column|Collection contract_address_lp
     * @property Grid\Column|Collection pancake_cate
     * @property Grid\Column|Collection is_platform
     * @property Grid\Column|Collection is_fan
     * @property Grid\Column|Collection is_del
     * @property Grid\Column|Collection usdt_value
     * @property Grid\Column|Collection coin_price
     * @property Grid\Column|Collection coin_num
     * @property Grid\Column|Collection ticket_num
     * @property Grid\Column|Collection is_get
     * @property Grid\Column|Collection pay_status
     * @property Grid\Column|Collection pay_time
     * @property Grid\Column|Collection currency_id
     * @property Grid\Column|Collection total
     * @property Grid\Column|Collection residue_total
     * @property Grid\Column|Collection from_user_id
     * @property Grid\Column|Collection cate
     * @property Grid\Column|Collection msg
     * @property Grid\Column|Collection game_team_id
     * @property Grid\Column|Collection wallet
     * @property Grid\Column|Collection path
     * @property Grid\Column|Collection level
     * @property Grid\Column|Collection code
     * @property Grid\Column|Collection ticket
     * @property Grid\Column|Collection usdt
     * @property Grid\Column|Collection usdt_cj
     * @property Grid\Column|Collection usdt_cs
     * @property Grid\Column|Collection rank
     * @property Grid\Column|Collection is_active
     * @property Grid\Column|Collection tday_join
     * @property Grid\Column|Collection yday_join
     * @property Grid\Column|Collection active_etime
     * @property Grid\Column|Collection group_num
     * @property Grid\Column|Collection achievement
     * @property Grid\Column|Collection performance
     * @property Grid\Column|Collection total_performance
     * @property Grid\Column|Collection machine_win_total
     * @property Grid\Column|Collection machine_cash_usdt
     * @property Grid\Column|Collection headimgurl
     * @property Grid\Column|Collection p_id
     * @property Grid\Column|Collection p_ordernum
     * @property Grid\Column|Collection receive_address
     * @property Grid\Column|Collection coin_type
     * @property Grid\Column|Collection w_type
     * @property Grid\Column|Collection fee
     * @property Grid\Column|Collection fee_amount
     * @property Grid\Column|Collection ac_amount
     * @property Grid\Column|Collection finsh_time
     * @property Grid\Column|Collection end_time
     * @property Grid\Column|Collection is_repeat
     * @property Grid\Column|Collection repeat_num
     * @property Grid\Column|Collection tday_price
     * @property Grid\Column|Collection yday_price
     *
     * @method Grid\Column|Collection id(string $label = null)
     * @method Grid\Column|Collection name(string $label = null)
     * @method Grid\Column|Collection type(string $label = null)
     * @method Grid\Column|Collection version(string $label = null)
     * @method Grid\Column|Collection detail(string $label = null)
     * @method Grid\Column|Collection created_at(string $label = null)
     * @method Grid\Column|Collection updated_at(string $label = null)
     * @method Grid\Column|Collection is_enabled(string $label = null)
     * @method Grid\Column|Collection parent_id(string $label = null)
     * @method Grid\Column|Collection order(string $label = null)
     * @method Grid\Column|Collection icon(string $label = null)
     * @method Grid\Column|Collection uri(string $label = null)
     * @method Grid\Column|Collection extension(string $label = null)
     * @method Grid\Column|Collection permission_id(string $label = null)
     * @method Grid\Column|Collection menu_id(string $label = null)
     * @method Grid\Column|Collection slug(string $label = null)
     * @method Grid\Column|Collection http_method(string $label = null)
     * @method Grid\Column|Collection http_path(string $label = null)
     * @method Grid\Column|Collection role_id(string $label = null)
     * @method Grid\Column|Collection user_id(string $label = null)
     * @method Grid\Column|Collection value(string $label = null)
     * @method Grid\Column|Collection username(string $label = null)
     * @method Grid\Column|Collection password(string $label = null)
     * @method Grid\Column|Collection avatar(string $label = null)
     * @method Grid\Column|Collection remember_token(string $label = null)
     * @method Grid\Column|Collection banner(string $label = null)
     * @method Grid\Column|Collection vedio(string $label = null)
     * @method Grid\Column|Collection status(string $label = null)
     * @method Grid\Column|Collection sort(string $label = null)
     * @method Grid\Column|Collection lang(string $label = null)
     * @method Grid\Column|Collection bigkey(string $label = null)
     * @method Grid\Column|Collection num(string $label = null)
     * @method Grid\Column|Collection content(string $label = null)
     * @method Grid\Column|Collection tab(string $label = null)
     * @method Grid\Column|Collection key(string $label = null)
     * @method Grid\Column|Collection help(string $label = null)
     * @method Grid\Column|Collection element(string $label = null)
     * @method Grid\Column|Collection rule(string $label = null)
     * @method Grid\Column|Collection hash(string $label = null)
     * @method Grid\Column|Collection round(string $label = null)
     * @method Grid\Column|Collection mark(string $label = null)
     * @method Grid\Column|Collection token(string $label = null)
     * @method Grid\Column|Collection user(string $label = null)
     * @method Grid\Column|Collection amount(string $label = null)
     * @method Grid\Column|Collection time(string $label = null)
     * @method Grid\Column|Collection uuid(string $label = null)
     * @method Grid\Column|Collection connection(string $label = null)
     * @method Grid\Column|Collection queue(string $label = null)
     * @method Grid\Column|Collection payload(string $label = null)
     * @method Grid\Column|Collection exception(string $label = null)
     * @method Grid\Column|Collection failed_at(string $label = null)
     * @method Grid\Column|Collection ordernum(string $label = null)
     * @method Grid\Column|Collection team_id(string $label = null)
     * @method Grid\Column|Collection join_usdt(string $label = null)
     * @method Grid\Column|Collection join_ticket(string $label = null)
     * @method Grid\Column|Collection state(string $label = null)
     * @method Grid\Column|Collection is_win(string $label = null)
     * @method Grid\Column|Collection not_reward(string $label = null)
     * @method Grid\Column|Collection team_num(string $label = null)
     * @method Grid\Column|Collection join_num(string $label = null)
     * @method Grid\Column|Collection is_settle(string $label = null)
     * @method Grid\Column|Collection dynamic_settle(string $label = null)
     * @method Grid\Column|Collection kj_time(string $label = null)
     * @method Grid\Column|Collection total_static(string $label = null)
     * @method Grid\Column|Collection total_dynamic(string $label = null)
     * @method Grid\Column|Collection total_sediment(string $label = null)
     * @method Grid\Column|Collection is_gcash(string $label = null)
     * @method Grid\Column|Collection is_tcash(string $label = null)
     * @method Grid\Column|Collection coin_img(string $label = null)
     * @method Grid\Column|Collection rate(string $label = null)
     * @method Grid\Column|Collection contract_address(string $label = null)
     * @method Grid\Column|Collection precision(string $label = null)
     * @method Grid\Column|Collection cj_pool(string $label = null)
     * @method Grid\Column|Collection cs_pool(string $label = null)
     * @method Grid\Column|Collection lp_pool(string $label = null)
     * @method Grid\Column|Collection lv(string $label = null)
     * @method Grid\Column|Collection zhi_num(string $label = null)
     * @method Grid\Column|Collection under_num(string $label = null)
     * @method Grid\Column|Collection line_num(string $label = null)
     * @method Grid\Column|Collection line_rank(string $label = null)
     * @method Grid\Column|Collection index(string $label = null)
     * @method Grid\Column|Collection recharge_id(string $label = null)
     * @method Grid\Column|Collection symbol(string $label = null)
     * @method Grid\Column|Collection price(string $label = null)
     * @method Grid\Column|Collection contract_address_lp(string $label = null)
     * @method Grid\Column|Collection pancake_cate(string $label = null)
     * @method Grid\Column|Collection is_platform(string $label = null)
     * @method Grid\Column|Collection is_fan(string $label = null)
     * @method Grid\Column|Collection is_del(string $label = null)
     * @method Grid\Column|Collection usdt_value(string $label = null)
     * @method Grid\Column|Collection coin_price(string $label = null)
     * @method Grid\Column|Collection coin_num(string $label = null)
     * @method Grid\Column|Collection ticket_num(string $label = null)
     * @method Grid\Column|Collection is_get(string $label = null)
     * @method Grid\Column|Collection pay_status(string $label = null)
     * @method Grid\Column|Collection pay_time(string $label = null)
     * @method Grid\Column|Collection currency_id(string $label = null)
     * @method Grid\Column|Collection total(string $label = null)
     * @method Grid\Column|Collection residue_total(string $label = null)
     * @method Grid\Column|Collection from_user_id(string $label = null)
     * @method Grid\Column|Collection cate(string $label = null)
     * @method Grid\Column|Collection msg(string $label = null)
     * @method Grid\Column|Collection game_team_id(string $label = null)
     * @method Grid\Column|Collection wallet(string $label = null)
     * @method Grid\Column|Collection path(string $label = null)
     * @method Grid\Column|Collection level(string $label = null)
     * @method Grid\Column|Collection code(string $label = null)
     * @method Grid\Column|Collection ticket(string $label = null)
     * @method Grid\Column|Collection usdt(string $label = null)
     * @method Grid\Column|Collection usdt_cj(string $label = null)
     * @method Grid\Column|Collection usdt_cs(string $label = null)
     * @method Grid\Column|Collection rank(string $label = null)
     * @method Grid\Column|Collection is_active(string $label = null)
     * @method Grid\Column|Collection tday_join(string $label = null)
     * @method Grid\Column|Collection yday_join(string $label = null)
     * @method Grid\Column|Collection active_etime(string $label = null)
     * @method Grid\Column|Collection group_num(string $label = null)
     * @method Grid\Column|Collection achievement(string $label = null)
     * @method Grid\Column|Collection performance(string $label = null)
     * @method Grid\Column|Collection total_performance(string $label = null)
     * @method Grid\Column|Collection machine_win_total(string $label = null)
     * @method Grid\Column|Collection machine_cash_usdt(string $label = null)
     * @method Grid\Column|Collection headimgurl(string $label = null)
     * @method Grid\Column|Collection p_id(string $label = null)
     * @method Grid\Column|Collection p_ordernum(string $label = null)
     * @method Grid\Column|Collection receive_address(string $label = null)
     * @method Grid\Column|Collection coin_type(string $label = null)
     * @method Grid\Column|Collection w_type(string $label = null)
     * @method Grid\Column|Collection fee(string $label = null)
     * @method Grid\Column|Collection fee_amount(string $label = null)
     * @method Grid\Column|Collection ac_amount(string $label = null)
     * @method Grid\Column|Collection finsh_time(string $label = null)
     * @method Grid\Column|Collection end_time(string $label = null)
     * @method Grid\Column|Collection is_repeat(string $label = null)
     * @method Grid\Column|Collection repeat_num(string $label = null)
     * @method Grid\Column|Collection tday_price(string $label = null)
     * @method Grid\Column|Collection yday_price(string $label = null)
     */
    class Grid {}

    class MiniGrid extends Grid {}

    /**
     * @property Show\Field|Collection id
     * @property Show\Field|Collection name
     * @property Show\Field|Collection type
     * @property Show\Field|Collection version
     * @property Show\Field|Collection detail
     * @property Show\Field|Collection created_at
     * @property Show\Field|Collection updated_at
     * @property Show\Field|Collection is_enabled
     * @property Show\Field|Collection parent_id
     * @property Show\Field|Collection order
     * @property Show\Field|Collection icon
     * @property Show\Field|Collection uri
     * @property Show\Field|Collection extension
     * @property Show\Field|Collection permission_id
     * @property Show\Field|Collection menu_id
     * @property Show\Field|Collection slug
     * @property Show\Field|Collection http_method
     * @property Show\Field|Collection http_path
     * @property Show\Field|Collection role_id
     * @property Show\Field|Collection user_id
     * @property Show\Field|Collection value
     * @property Show\Field|Collection username
     * @property Show\Field|Collection password
     * @property Show\Field|Collection avatar
     * @property Show\Field|Collection remember_token
     * @property Show\Field|Collection banner
     * @property Show\Field|Collection vedio
     * @property Show\Field|Collection status
     * @property Show\Field|Collection sort
     * @property Show\Field|Collection lang
     * @property Show\Field|Collection bigkey
     * @property Show\Field|Collection num
     * @property Show\Field|Collection content
     * @property Show\Field|Collection tab
     * @property Show\Field|Collection key
     * @property Show\Field|Collection help
     * @property Show\Field|Collection element
     * @property Show\Field|Collection rule
     * @property Show\Field|Collection hash
     * @property Show\Field|Collection round
     * @property Show\Field|Collection mark
     * @property Show\Field|Collection token
     * @property Show\Field|Collection user
     * @property Show\Field|Collection amount
     * @property Show\Field|Collection time
     * @property Show\Field|Collection uuid
     * @property Show\Field|Collection connection
     * @property Show\Field|Collection queue
     * @property Show\Field|Collection payload
     * @property Show\Field|Collection exception
     * @property Show\Field|Collection failed_at
     * @property Show\Field|Collection ordernum
     * @property Show\Field|Collection team_id
     * @property Show\Field|Collection join_usdt
     * @property Show\Field|Collection join_ticket
     * @property Show\Field|Collection state
     * @property Show\Field|Collection is_win
     * @property Show\Field|Collection not_reward
     * @property Show\Field|Collection team_num
     * @property Show\Field|Collection join_num
     * @property Show\Field|Collection is_settle
     * @property Show\Field|Collection dynamic_settle
     * @property Show\Field|Collection kj_time
     * @property Show\Field|Collection total_static
     * @property Show\Field|Collection total_dynamic
     * @property Show\Field|Collection total_sediment
     * @property Show\Field|Collection is_gcash
     * @property Show\Field|Collection is_tcash
     * @property Show\Field|Collection coin_img
     * @property Show\Field|Collection rate
     * @property Show\Field|Collection contract_address
     * @property Show\Field|Collection precision
     * @property Show\Field|Collection cj_pool
     * @property Show\Field|Collection cs_pool
     * @property Show\Field|Collection lp_pool
     * @property Show\Field|Collection lv
     * @property Show\Field|Collection zhi_num
     * @property Show\Field|Collection under_num
     * @property Show\Field|Collection line_num
     * @property Show\Field|Collection line_rank
     * @property Show\Field|Collection index
     * @property Show\Field|Collection recharge_id
     * @property Show\Field|Collection symbol
     * @property Show\Field|Collection price
     * @property Show\Field|Collection contract_address_lp
     * @property Show\Field|Collection pancake_cate
     * @property Show\Field|Collection is_platform
     * @property Show\Field|Collection is_fan
     * @property Show\Field|Collection is_del
     * @property Show\Field|Collection usdt_value
     * @property Show\Field|Collection coin_price
     * @property Show\Field|Collection coin_num
     * @property Show\Field|Collection ticket_num
     * @property Show\Field|Collection is_get
     * @property Show\Field|Collection pay_status
     * @property Show\Field|Collection pay_time
     * @property Show\Field|Collection currency_id
     * @property Show\Field|Collection total
     * @property Show\Field|Collection residue_total
     * @property Show\Field|Collection from_user_id
     * @property Show\Field|Collection cate
     * @property Show\Field|Collection msg
     * @property Show\Field|Collection game_team_id
     * @property Show\Field|Collection wallet
     * @property Show\Field|Collection path
     * @property Show\Field|Collection level
     * @property Show\Field|Collection code
     * @property Show\Field|Collection ticket
     * @property Show\Field|Collection usdt
     * @property Show\Field|Collection usdt_cj
     * @property Show\Field|Collection usdt_cs
     * @property Show\Field|Collection rank
     * @property Show\Field|Collection is_active
     * @property Show\Field|Collection tday_join
     * @property Show\Field|Collection yday_join
     * @property Show\Field|Collection active_etime
     * @property Show\Field|Collection group_num
     * @property Show\Field|Collection achievement
     * @property Show\Field|Collection performance
     * @property Show\Field|Collection total_performance
     * @property Show\Field|Collection machine_win_total
     * @property Show\Field|Collection machine_cash_usdt
     * @property Show\Field|Collection headimgurl
     * @property Show\Field|Collection p_id
     * @property Show\Field|Collection p_ordernum
     * @property Show\Field|Collection receive_address
     * @property Show\Field|Collection coin_type
     * @property Show\Field|Collection w_type
     * @property Show\Field|Collection fee
     * @property Show\Field|Collection fee_amount
     * @property Show\Field|Collection ac_amount
     * @property Show\Field|Collection finsh_time
     * @property Show\Field|Collection end_time
     * @property Show\Field|Collection is_repeat
     * @property Show\Field|Collection repeat_num
     * @property Show\Field|Collection tday_price
     * @property Show\Field|Collection yday_price
     *
     * @method Show\Field|Collection id(string $label = null)
     * @method Show\Field|Collection name(string $label = null)
     * @method Show\Field|Collection type(string $label = null)
     * @method Show\Field|Collection version(string $label = null)
     * @method Show\Field|Collection detail(string $label = null)
     * @method Show\Field|Collection created_at(string $label = null)
     * @method Show\Field|Collection updated_at(string $label = null)
     * @method Show\Field|Collection is_enabled(string $label = null)
     * @method Show\Field|Collection parent_id(string $label = null)
     * @method Show\Field|Collection order(string $label = null)
     * @method Show\Field|Collection icon(string $label = null)
     * @method Show\Field|Collection uri(string $label = null)
     * @method Show\Field|Collection extension(string $label = null)
     * @method Show\Field|Collection permission_id(string $label = null)
     * @method Show\Field|Collection menu_id(string $label = null)
     * @method Show\Field|Collection slug(string $label = null)
     * @method Show\Field|Collection http_method(string $label = null)
     * @method Show\Field|Collection http_path(string $label = null)
     * @method Show\Field|Collection role_id(string $label = null)
     * @method Show\Field|Collection user_id(string $label = null)
     * @method Show\Field|Collection value(string $label = null)
     * @method Show\Field|Collection username(string $label = null)
     * @method Show\Field|Collection password(string $label = null)
     * @method Show\Field|Collection avatar(string $label = null)
     * @method Show\Field|Collection remember_token(string $label = null)
     * @method Show\Field|Collection banner(string $label = null)
     * @method Show\Field|Collection vedio(string $label = null)
     * @method Show\Field|Collection status(string $label = null)
     * @method Show\Field|Collection sort(string $label = null)
     * @method Show\Field|Collection lang(string $label = null)
     * @method Show\Field|Collection bigkey(string $label = null)
     * @method Show\Field|Collection num(string $label = null)
     * @method Show\Field|Collection content(string $label = null)
     * @method Show\Field|Collection tab(string $label = null)
     * @method Show\Field|Collection key(string $label = null)
     * @method Show\Field|Collection help(string $label = null)
     * @method Show\Field|Collection element(string $label = null)
     * @method Show\Field|Collection rule(string $label = null)
     * @method Show\Field|Collection hash(string $label = null)
     * @method Show\Field|Collection round(string $label = null)
     * @method Show\Field|Collection mark(string $label = null)
     * @method Show\Field|Collection token(string $label = null)
     * @method Show\Field|Collection user(string $label = null)
     * @method Show\Field|Collection amount(string $label = null)
     * @method Show\Field|Collection time(string $label = null)
     * @method Show\Field|Collection uuid(string $label = null)
     * @method Show\Field|Collection connection(string $label = null)
     * @method Show\Field|Collection queue(string $label = null)
     * @method Show\Field|Collection payload(string $label = null)
     * @method Show\Field|Collection exception(string $label = null)
     * @method Show\Field|Collection failed_at(string $label = null)
     * @method Show\Field|Collection ordernum(string $label = null)
     * @method Show\Field|Collection team_id(string $label = null)
     * @method Show\Field|Collection join_usdt(string $label = null)
     * @method Show\Field|Collection join_ticket(string $label = null)
     * @method Show\Field|Collection state(string $label = null)
     * @method Show\Field|Collection is_win(string $label = null)
     * @method Show\Field|Collection not_reward(string $label = null)
     * @method Show\Field|Collection team_num(string $label = null)
     * @method Show\Field|Collection join_num(string $label = null)
     * @method Show\Field|Collection is_settle(string $label = null)
     * @method Show\Field|Collection dynamic_settle(string $label = null)
     * @method Show\Field|Collection kj_time(string $label = null)
     * @method Show\Field|Collection total_static(string $label = null)
     * @method Show\Field|Collection total_dynamic(string $label = null)
     * @method Show\Field|Collection total_sediment(string $label = null)
     * @method Show\Field|Collection is_gcash(string $label = null)
     * @method Show\Field|Collection is_tcash(string $label = null)
     * @method Show\Field|Collection coin_img(string $label = null)
     * @method Show\Field|Collection rate(string $label = null)
     * @method Show\Field|Collection contract_address(string $label = null)
     * @method Show\Field|Collection precision(string $label = null)
     * @method Show\Field|Collection cj_pool(string $label = null)
     * @method Show\Field|Collection cs_pool(string $label = null)
     * @method Show\Field|Collection lp_pool(string $label = null)
     * @method Show\Field|Collection lv(string $label = null)
     * @method Show\Field|Collection zhi_num(string $label = null)
     * @method Show\Field|Collection under_num(string $label = null)
     * @method Show\Field|Collection line_num(string $label = null)
     * @method Show\Field|Collection line_rank(string $label = null)
     * @method Show\Field|Collection index(string $label = null)
     * @method Show\Field|Collection recharge_id(string $label = null)
     * @method Show\Field|Collection symbol(string $label = null)
     * @method Show\Field|Collection price(string $label = null)
     * @method Show\Field|Collection contract_address_lp(string $label = null)
     * @method Show\Field|Collection pancake_cate(string $label = null)
     * @method Show\Field|Collection is_platform(string $label = null)
     * @method Show\Field|Collection is_fan(string $label = null)
     * @method Show\Field|Collection is_del(string $label = null)
     * @method Show\Field|Collection usdt_value(string $label = null)
     * @method Show\Field|Collection coin_price(string $label = null)
     * @method Show\Field|Collection coin_num(string $label = null)
     * @method Show\Field|Collection ticket_num(string $label = null)
     * @method Show\Field|Collection is_get(string $label = null)
     * @method Show\Field|Collection pay_status(string $label = null)
     * @method Show\Field|Collection pay_time(string $label = null)
     * @method Show\Field|Collection currency_id(string $label = null)
     * @method Show\Field|Collection total(string $label = null)
     * @method Show\Field|Collection residue_total(string $label = null)
     * @method Show\Field|Collection from_user_id(string $label = null)
     * @method Show\Field|Collection cate(string $label = null)
     * @method Show\Field|Collection msg(string $label = null)
     * @method Show\Field|Collection game_team_id(string $label = null)
     * @method Show\Field|Collection wallet(string $label = null)
     * @method Show\Field|Collection path(string $label = null)
     * @method Show\Field|Collection level(string $label = null)
     * @method Show\Field|Collection code(string $label = null)
     * @method Show\Field|Collection ticket(string $label = null)
     * @method Show\Field|Collection usdt(string $label = null)
     * @method Show\Field|Collection usdt_cj(string $label = null)
     * @method Show\Field|Collection usdt_cs(string $label = null)
     * @method Show\Field|Collection rank(string $label = null)
     * @method Show\Field|Collection is_active(string $label = null)
     * @method Show\Field|Collection tday_join(string $label = null)
     * @method Show\Field|Collection yday_join(string $label = null)
     * @method Show\Field|Collection active_etime(string $label = null)
     * @method Show\Field|Collection group_num(string $label = null)
     * @method Show\Field|Collection achievement(string $label = null)
     * @method Show\Field|Collection performance(string $label = null)
     * @method Show\Field|Collection total_performance(string $label = null)
     * @method Show\Field|Collection machine_win_total(string $label = null)
     * @method Show\Field|Collection machine_cash_usdt(string $label = null)
     * @method Show\Field|Collection headimgurl(string $label = null)
     * @method Show\Field|Collection p_id(string $label = null)
     * @method Show\Field|Collection p_ordernum(string $label = null)
     * @method Show\Field|Collection receive_address(string $label = null)
     * @method Show\Field|Collection coin_type(string $label = null)
     * @method Show\Field|Collection w_type(string $label = null)
     * @method Show\Field|Collection fee(string $label = null)
     * @method Show\Field|Collection fee_amount(string $label = null)
     * @method Show\Field|Collection ac_amount(string $label = null)
     * @method Show\Field|Collection finsh_time(string $label = null)
     * @method Show\Field|Collection end_time(string $label = null)
     * @method Show\Field|Collection is_repeat(string $label = null)
     * @method Show\Field|Collection repeat_num(string $label = null)
     * @method Show\Field|Collection tday_price(string $label = null)
     * @method Show\Field|Collection yday_price(string $label = null)
     */
    class Show {}

    /**
     * @method \SuperEggs\DcatDistpicker\Form\Distpicker distpicker(...$params)
     */
    class Form {}

}

namespace Dcat\Admin\Grid {
    /**
     * @method $this lightbox(...$params)
     * @method $this video(...$params)
     * @method $this audio(...$params)
     * @method $this distpicker(...$params)
     */
    class Column {}

    /**
     * @method \SuperEggs\DcatDistpicker\Filter\DistpickerFilter distpicker(...$params)
     */
    class Filter {}
}

namespace Dcat\Admin\Show {
    /**
     * @method $this lightbox(...$params)
     * @method $this video(...$params)
     * @method $this audio(...$params)
     */
    class Field {}
}
