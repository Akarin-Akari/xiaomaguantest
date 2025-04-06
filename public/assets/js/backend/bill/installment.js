define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bill/installment/index' + location.search,
                    add_url: 'bill/installment/add',
                    edit_url: 'bill/installment/edit',
                    del_url: 'bill/installment/del',
                    multi_url: 'bill/installment/multi',
                    import_url: 'bill/installment/import',
                    table: 'installment',
                }
            });

            var table = $("#table");
            var submitForm = function (ids, layero) {
                var options = table.bootstrapTable('getOptions');
                console.log(options);
                var columns = [];
                $.each(options.columns[0], function (i, j) {
                    if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                        columns.push(j.field);
                    }
                });
                var search = options.queryParams({});
                $("input[name=search]", layero).val(options.searchText);
                $("input[name=ids]", layero).val(ids);
                $("input[name=filter]", layero).val(search.filter);
                $("input[name=op]", layero).val(search.op);
                $("input[name=columns]", layero).val(columns.join(','));
                $("form", layero).submit();
            };
            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                console.log(data);
                //这里我们手动设置底部的值
                $("#extend_money").text(data.money);
            });
            $(document).on("click", ".btn-export", function () {
                var ids = Table.api.selectedids(table);
                var page = table.bootstrapTable('getData');
                var all = table.bootstrapTable('getOptions').totalRows;
                console.log(ids, page, all);
                Layer.confirm("请选择导出的选项<form action='" + Fast.api.fixurl("bill/installment/export") + "' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'></form>", {
                    title: '导出数据',
                    btn: ["选中项(" + ids.length + "条)", "本页(" + page.length + "条)", "全部(" + all + "条)"],
                    success: function (layero, index) {
                        $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                    }
                    , yes: function (index, layero) {
                        submitForm(ids.join(","), layero);
                        return false;
                    }
                    ,
                    btn2: function (index, layero) {
                        var ids = [];
                        $.each(page, function (i, j) {
                            ids.push(j.id);
                        });
                        submitForm(ids.join(","), layero);
                        return false;
                    }
                    ,
                    btn3: function (index, layero) {
                        submitForm("all", layero);
                        return false;
                    }
                })
            });
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
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id'), operate: 'LIKE'},
                        {field: 'student.name', title: __('Student.name'), operate: 'LIKE'},
                        // {field: 'times', title: __('Times')},
                        {field: 'fundsclass.name', title: __('Fundsclass.name')},
                        {field: 'money', title: __('Money')},
                        // {field: 'platform_number', title: __('Platform_number'), operate: 'LIKE'},
                        {field: 'payment_number', title: __('Payment_number'), operate: 'LIKE'},
                        {field: 'paymentsource.payment_source', title: __('Paymentsource.payment_source')},
                        {field: 'fundsclass.state', title: __('Fundsclass.state'), searchList: {"income":__('Fundsclass.state income'),"expenditure":__('Fundsclass.state expenditure')}, formatter: Table.api.formatter.normal},

                        // {field: 'pay_status', title: __('Pay_status'), searchList: {"yes":__('Pay_status yes'),"no":__('Pay_status no')}, formatter: Table.api.formatter.status},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'audit', title: __('Audit'), searchList: {"yes":__('Audit yes'),"no":__('Audit no')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    title: 'audit',
                                    text: '未审核',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    confirm: '是否审核通过',
                                    url: 'bill/installment/audit',
                                    hidden:function(rows){
                                        if(rows.audit == 'yes'){
                                            return true;
                                        }
                                    },
                                    success:function(data, ret){
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'audit',
                                    text: '已审核',
                                    title: __('已审核'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    // confirm: '是否审核通过',
                                    url: 'bill/installment/audit',
                                    hidden:function(rows){
                                        if(rows.audit == 'no'){
                                            return true;
                                        }
                                    },
                                    disable:function(rows){
                                        if(rows.audit == 'yes'){
                                            return true;
                                        }
                                    }
                                    
                                }
                            ],
                        }
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
                url: 'bill/installment/recyclebin' + location.search,
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
                                    url: 'bill/installment/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'bill/installment/destroy',
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