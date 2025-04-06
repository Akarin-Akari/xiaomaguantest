define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'config/cooperation/index' + location.search,
                    add_url: 'config/cooperation/add',
                    edit_url: 'config/cooperation/edit',
                    del_url: 'config/cooperation/del',
                    multi_url: 'config/cooperation/multi',
                    import_url: 'config/cooperation/import',
                    table: 'cooperation',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                //启用固定列
                fixedColumns: true,
                //固定右侧列数
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        // {field: 'admin_title', title: __('Admin_title'), operate: 'LIKE'},
                        {field: 'reserve_day', title: __('Reserve_day')},
                        {field: 'icon_image', title: __('Icon_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'ai_agree_sc', title: __('Ai_agree_sc'), searchList: {"0":__('Ai_agree_sc 0'),"1":__('Ai_agree_sc 1')}, formatter: Table.api.formatter.normal},
                        {field: 'ai_pass_coach', title: __('Ai_pass_coach'), searchList: {"0":__('Ai_pass_coach 0'),"1":__('Ai_pass_coach 1')}, formatter: Table.api.formatter.normal},
                        {field: 'keer_pass_sc', title: __('Keer_pass_sc'), searchList: {"0":__('Keer_pass_sc 0'),"1":__('Keer_pass_sc 1')}, formatter: Table.api.formatter.normal},
                        {field: 'keer_pass_time', title: __('Keer_pass_time')},
                        {field: 'kesan_pass_sc', title: __('Kesan_pass_sc'), searchList: {"0":__('Kesan_pass_sc 0'),"1":__('Kesan_pass_sc 1')}, formatter: Table.api.formatter.normal},
                        {field: 'kesan_pass_time', title: __('Kesan_pass_time')},
                        {field: 'distribute_state', title: __('Distribute_state'), searchList: {"0":__('Distribute_state 0'),"1":__('Distribute_state 1')}, formatter: Table.api.formatter.normal},
                        {field: 'forbidden_pay_state', title: __('Forbidden_pay_state'), searchList: {"0":__('Forbidden_pay_state 0'),"1":__('Forbidden_pay_state 1')}, formatter: Table.api.formatter.normal},
                        {field: 'forbidden_tmp_stu', title: __('Forbidden_tmp_stu'), searchList: {"0":__('Forbidden_tmp_stu 0'),"1":__('Forbidden_tmp_stu 1')}, formatter: Table.api.formatter.normal},
                        {field: 'forbidden_not_reserve', title: __('Forbidden_not_reserve'), searchList: {"0":__('Forbidden_not_reserve 0'),"1":__('Forbidden_not_reserve 1')}, formatter: Table.api.formatter.normal},
                        {field: 'promote_day_state', title: __('Promote_day_state'), searchList: {"0":__('Promote_day_state 0'),"1":__('Promote_day_state 1')}, formatter: Table.api.formatter.normal},
                        {field: 'promote_day', title: __('Promote_day')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'config/cooperation/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'config/cooperation/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'config/cooperation/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});