define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spaceconfig/kesansort/index' + location.search,
                    add_url: 'spaceconfig/kesansort/add',
                    edit_url: 'spaceconfig/kesansort/edit',
                    del_url: 'spaceconfig/kesansort/del',
                    multi_url: 'spaceconfig/kesansort/multi',
                    import_url: 'spaceconfig/kesansort/import',
                    table: 'kesan_sort',
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
                        {field: 'place.place_name', title: __('Place.place_name'), operate: 'LIKE'},
                        {field: 'sequence', title: __('Sequence'), operate: 'LIKE',
                        formatter : function(value, row, index, field){
                                return "<span style='display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;' title='" + row.sequence + "'>" + value + "</span>";
                            },
                            cellStyle : function(value, row, index, field){
                                return {
                                    css: {
                                        "white-space": "nowrap",
                                        "text-overflow": "ellipsis",
                                        "overflow": "hidden",
                                        "max-width":"200px"
                                    }
                                };
                            }
                        },
                        {field: 'process_name', title: __('Process_name'), operate: false,
                            formatter : function(value, row, index, field){
                                return "<span style='display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;' title='" + row.process_name + "'>" + value + "</span>";
                            },
                            cellStyle : function(value, row, index, field){
                                return {
                                    css: {
                                        "white-space": "nowrap",
                                        "text-overflow": "ellipsis",
                                        "overflow": "hidden",
                                        "max-width":"200px"
                                    }
                                };
                            }
                        },
                        {field: 'kesan_c1_number', title: __('Kesan_c1_number'), operate: 'LIKE'},
                        {field: 'kesan_c2_number', title: __('Kesan_c2_number'), operate: 'LIKE'},
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