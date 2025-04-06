define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'statistic/trainstatistic/index' + location.search,
                    // add_url: 'statistic/trainstatistic/add',
                    // edit_url: 'statistic/trainstatistic/edit',
                    // del_url: 'statistic/trainstatistic/del',
                    multi_url: 'statistic/trainstatistic/multi',
                    import_url: 'statistic/trainstatistic/import',
                    table: 'train_statistic',
                }
            });

            var table = $("#table");
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                console.log(data);
                //这里我们手动设置底部的值
                $("#extend_money").text(data.total);
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,

                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id'), operate: 'LIKE'},
                        {field: 'total_order', title: __('Total_order')},
                        {field: 'keer_hege', title: __('Keer_hege'), operate:'BETWEEN'},
                        {field: 'keer_leiji', title: __('Keer_leiji')},
                        {field: 'kesan_hege', title: __('Kesan_hege'), operate:'BETWEEN'},
                        {field: 'kesan_leiji', title: __('Kesan_leiji')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        { 
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            buttons: [
                                {
                                    name: '科二详情',
                                    text: '科二详情',
                                    title: __('科二详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'statistic/trainstatistic/detail1',
                                    
                                },
                                {
                                    name: '科三详情',
                                    text: '科三详情',
                                    title: __('科三详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'statistic/trainstatistic/detail2',
                                    
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        },
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