define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bill/percentage/index' + location.search,
                    // add_url: 'bill/percentage/add',
                    // edit_url: 'bill/percentage/edit',
                    // del_url: 'bill/percentage/del',
                    multi_url: 'bill/percentage/statement',
                    import_url: 'bill/percentage/import',
                    table: 'student',
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
                        {checkbox: true,
                            formatter:function (value,row,index) {
                                if(row.statement == '1'){
                                  return {
                                    disabled: true
                                  };
                                }
                            }
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'admin.nickname', title: __('Admin.nickname'), operate: 'LIKE'},
                        {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'stu_id', title: __('Stu_id')},
                        {field: 'recommender.name', title: __('Recommender.name'),  operate: 'LIKE'},
                        {field: 'percentage', title: __('Percentage'), operate:'BETWEEN'},
                        // {field: 'statement', title: __('Statement'), searchList: {"0":__('Statement 0'),"1":__('Statement 1')},operate:'FIND_IN_SET', formatter: Table.api.formatter.label,
                        // custom:{'0':'danger','1':'success'}  },
                        {field: 'operate',  title: __('Operate'), table: table, events: Table.api.events.operate, 
                            buttons: [
                                {
                                    title: '去结算',
                                    text: '未结算',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    confirm: '是否结算当前跟进人的提成',
                                    url: 'bill/percentage/statement',
                                    hidden:function(rows){
                                        if(rows.statement == '1'){
                                            return true;
                                        }
                                    },
                                    success:function(data, ret){
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: '已结算',
                                    text: '已结算',
                                    title: __('已结算'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    // confirm: '是否审核通过',
                                    url: 'bill/percentage/statement',
                                    hidden:function(rows){
                                        if(rows.statement == '0'){
                                            return true;
                                        }
                                    },
                                    disable:function(rows){
                                        if(rows.statement == '1'){
                                            return true;
                                        }
                                    }
                                    
                                }
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            $(document).on("click", ".btn-statement", function () {
                var that = this;
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定批量审核选中的 %s 项吗?', ids.length),
                    {icon: 3, title: __('Warning'), offset: 0, shadeClose: true},
                    function (index) {
                        Table.api.multi("multi", ids, table, that);
                        Layer.close(index);
                    }
                );

            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});