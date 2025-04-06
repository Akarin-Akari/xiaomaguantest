define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'statistic/machinemonth/index' + location.search,
                    // add_url: 'statistic/machinemonth/add',
                    // edit_url: 'statistic/machinemonth/edit',
                    // del_url: 'statistic/machinemonth/del',
                    multi_url: 'statistic/machinemonth/multi',
                    // import_url: 'statistic/machinemonth/import',
                    table: 'machine_car',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                columns: [
                    [
                        // {checkbox: true},
                        { field: 'id', title: __('Id') },
                        { field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE' },
                        { field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE' },
                        { field: 'machine_code', title: __('Machine_code'), operate: 'LIKE' },
                        { field: 'collor', title: __('Collor'), searchList: { "1": __('Collor 1'), "2": __('Collor 2'), "3": __('Collor 3'), "4": __('Collor 4') }, formatter: Table.api.formatter.normal },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: '本月数据',
                                    text: '本月数据',
                                    title: __('本月数据'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'statistic/machinemonth/detail1',

                                },
                                {
                                    name: '本年数据',
                                    text: '本年数据',
                                    title: __('本年数据'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'statistic/machinemonth/detail2',

                                }
                            ],
                            formatter: Table.api.formatter.operate
                        },
                        // {
                        //     field: 'operate',
                        //     title: __('Operate'), 
                        //     table: table, 
                        //     events: Table.api.events.operate,
                        //     button:[
                        //         {
                        //             name: 'detail',
                        //             text: '教员排课',
                        //             title: __('弹出窗口打开'),
                        //             classname: 'btn btn-xs btn-primary btn-dialog',
                        //             icon: 'fa fa-list',
                        //             url: 'statistic/machinemonth/detail1',
                        //             callback: function (data) {
                        //                 Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                        //             }
                        //         },
                        //         {
                        //             name: 'detail',
                        //             title: __('弹出窗口打开'),
                        //             classname: 'btn btn-xs btn-primary btn-dialog',
                        //             icon: 'fa fa-list',
                        //             url: 'statistic/machinemonth/detail2',
                        //             callback: function (data) {
                        //                 Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                        //             }
                        //         },
                        //     ],
                        //     formatter: Table.api.formatter.operate,
                        // }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            $(document).on('click', '.btn-callback', function () {
                Fast.api.close($("input[name=callback]").val());
            });
        },
        // add: function () {
        //     Controller.api.bindevent();
        // },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});