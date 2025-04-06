define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'statistic/keerhege/index' + location.search,
                    table: 'student_warn',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'keer_hege', title: __('Keer_hege'), operate: 'LIKE'},
                        {field: 'keer_hege_status', title: __('Keer_hege_status'),formatter: Controller.api.formatter.custom},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
                custom: function (value, row, index) {
                    //添加上btn-change可以自定义请求的URL进行数据处理
                    if(value ==0 ){
                        return '<a class="btn-change text-success" data-url="statistic/keerhege/change" data-confirm="确认开启预警？" data-id="' + row.id + '"><i class="fa ' + (row.title == '' ? 'fa-toggle-on fa-flip-horizontal text-gray' : 'fa-toggle-on') + ' fa-flip-horizontal text-gray fa-2x"></i></a>';
                    }else{
                        return '<a class="btn-change text-success" data-url="statistic/keerhege/change" data-confirm="确认关闭预警？" data-id="' + row.id + '"><i class="fa ' + (row.title == '' ? 'fa-toggle-on fa-flip-horizontal text-gray' : 'fa-toggle-on') + ' fa-2x"></i></a>';
                    }
                },
                
            }
            
        }
    };
    return Controller;
});