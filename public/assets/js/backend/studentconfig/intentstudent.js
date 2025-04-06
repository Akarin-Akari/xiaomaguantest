define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/intentstudent/index' + location.search,
                    add_url: 'studentconfig/intentstudent/add',
                    edit_url: 'studentconfig/intentstudent/edit',
                    del_url: 'studentconfig/intentstudent/del',
                    multi_url: 'studentconfig/intentstudent/multi',
                    import_url: 'studentconfig/intentstudent/import',
                    table: 'intent_student',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id')},
                        {field: 'phone', title: __('Phone')},
                        // {field: 'phone_text', title: __('Phone')},
                        {field: 'regis_status', title: __('Regis_Status'), searchList: {"1":__('Regis_status 1'),"2":__('Regis_status 2')}, formatter: Table.api.formatter.normal},
                        {field: 'sex', title: __('Sex'), searchList: {"male":__('Sex male'),"female":__('Sex female')}, formatter: Table.api.formatter.normal},
                        {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'recommender.name', title: __('Recommender.name')},
                        // {field: 'signupsource.sign_up_source_name', title: __('Signupsource.sign_up_source_name')},
                        {field: 'remarks', title: __('Remarks')},
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