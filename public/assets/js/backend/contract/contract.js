define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'contract/contract/index' + location.search,
                    add_url: 'contract/contract/add',
                    edit_url: 'contract/contract/edit',
                    del_url: 'contract/contract/del',
                    multi_url: 'contract/contract/multi',
                    // import_url: 'contract/contract/import',
                    table: 'contract',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE',formatter: Controller.api.formatter.thumb},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'state', title: __('State'), searchList: {"0":__('state 0'),"1":__('state 1')}, operate:'FIND_IN_SET', formatter: Table.api.formatter.label},
                        // {field: 'file_path', title: __('File_path'), operate: false},
                        {field: 'validitytime', title: __('Validitytime'), operate:'RANGE', autocomplete:false,},
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
        import: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                thumb:function (value, row, index) {
                    console.log(row)
                    return '<a href="' + row.file_path + '" target="_blank" class="label bg-green">' + row.name + '</a>';
                },
            }
            
        }
    };
    return Controller;
});