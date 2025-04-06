define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spaceconfig/car/index' + location.search,
                    add_url: 'spaceconfig/car/add',
                    edit_url: 'spaceconfig/car/edit',
                    del_url: 'spaceconfig/car/del',
                    multi_url: 'spaceconfig/car/multi',
                    import_url: 'spaceconfig/car/import',
                    table: 'car',
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
                        {field: 'machine_code', title: __('Machine_code'), operate: 'LIKE'},
                        {field: 'machineai.machine_code', title: __('Machineai.machine_code'), operate: 'LIKE'},
                        {field: 'brand', title: __('Brand'), operate: 'LIKE'},
                        {field: 'shape', title: __('Shape'), operate: 'LIKE'},
                        
                        {field: 'car_type', title: __('Car_type'), searchList: {"cartype1":__('Car_type cartype1'),"cartype2":__('Car_type cartype2')}, formatter: Table.api.formatter.label},
                        {field: 'subject_type', title: __('Subject_type'), searchList: {"subject2":__('Subject_type subject2'),"subject3":__('Subject_type subject3')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        {field: 'regionimage', title: __('Regionimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'student_code', title: __('Student_code'), operate: 'LIKE',events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images},

                        
                        {field: 'state', title: __('State'), searchList: {"0":__('State 0'),"1":__('State 1')}, formatter: Table.api.formatter.normal},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE'},
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
                url: 'spaceconfig/car/recyclebin' + location.search,
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
                                    url: 'spaceconfig/car/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'spaceconfig/car/destroy',
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
            
        },
        
    };
    return Controller;
});