define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'recommender/recommender/index' + location.search,
                    add_url: 'recommender/recommender/add',
                    edit_url: 'recommender/recommender/edit',
                    del_url: 'recommender/recommender/del',
                    multi_url: 'recommender/recommender/multi',
                    import_url: 'recommender/recommender/import',
                    table: 'recommender',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone')},
                        // {field: 'leader', title: __('Leader'),operate:false},
                        {field: 'status', title: __('Status'), searchList: {"yes":__('Status yes'),"no":__('Status no')}, formatter: Table.api.formatter.normal},
                        {field: 'message_status', title: __('Message_status'), searchList: {"yes":__('Message_status yes'),"female":__('Message_status no')}, formatter: Table.api.formatter.normal},
                        {field: 'card_type', title: __('Card_type'), searchList: {"0":__('Card_type 0'),"1":__('Card_type 1')}, formatter: Table.api.formatter.normal},
                        {field: 'car_type', title: __('Car_type'), searchList: {"1":__('Car_type 1'),"2":__('Car_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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