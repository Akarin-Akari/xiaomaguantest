define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'studentconfig/certificate/index' + location.search,
                    add_url: 'studentconfig/certificate/add',
                    edit_url: 'studentconfig/certificate/edit',
                    del_url: 'studentconfig/certificate/del',
                    multi_url: 'studentconfig/certificate/multi',
                    import_url: 'studentconfig/certificate/import',
                    table: 'student',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE',
                            formatter: function (value, row, index) {
                                if(!row.student) return '-';
                                value='<a href="javascript:void(0);" data-url="studentconfig/student/edit/ids/' + row.student.id
                                    +'" data-area="[&quot;100%&quot;,&quot;100%&quot;]" class="btn-dialog" data-title="'+ row.student.name+'">'+row.student.name+'</a>';
                                return value;
                            },
                        },
                        {field: 'phone', title: __('Phone')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    title: 'audit',
                                    text: '未审核',
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    confirm: '是否审核通过',
                                    url: 'studentconfig/certificate/audit',
                                    hidden:function(rows){
                                        if(rows.pz_status == '1'){
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
                                    url: 'studentconfig/certificate/audit',
                                    hidden:function(rows){
                                        if(rows.pz_status == '0'){
                                            return true;
                                        }
                                    },
                                    disable:function(rows){
                                        if(rows.pz_status == '1'){
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
                url: 'studentconfig/certificate/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '140px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'studentconfig/certificate/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'studentconfig/certificate/destroy',
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
