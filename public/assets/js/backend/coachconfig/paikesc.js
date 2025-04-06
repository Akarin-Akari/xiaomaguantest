define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init();
            this.table.first();
            // Form.api.bindevent(form, success, error, submit);
        },
        table: {
            first: function () {
                // 表格1
                var table = $("#table");
                table.bootstrapTable({
                    url: 'coachconfig/paikesc/index',
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    searchFormVisible:true,
                    columns: [
                        [
                            {field: 'id', title: 'ID'},
                            {field: 'space.space_name', title: __('Space.space_name'), operate: 'LIKE'},
                            {field: 'name', title: __('name')},
                            {
                                field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                    {
                                        title: '教员排课',
                                        text: '教员排课',
                                        classname: 'btn btn-primary btn-xs btn-ajax',
                                        icon: 'fa fa-list',
                                        // confirm: '确认发送Ajax请求？',
                                        url: 'coachconfig/paikesc/detail',
                                        success: function (data, ret) {
                                            console.log(ret)
                                            // for(i=0;i<6;i++){
                                            //     document.getElementById('getday'+i).style.backgroundColor = "#e7e7e7";
                                            // }
                                            // let new_day = document.getElementById('getday0');
                                            // new_day.style.backgroundColor = '#3498db';
                                            document.getElementById("coach_name").innerHTML = '当前教员：'+ret.msg.choose_coach;
                                            
                                            document.getElementById("item").innerHTML = '';
                                            content = '';
                                            for(i=0;i<ret.msg.time_list.length;i++){
                                                content +='<tr><th style="text-align: center; vertical-align: middle; " ><div class="th-inner ">'+ret.msg.time_list[i]['starttimes']+'-'+ret.msg.time_list[i]['endtimes']+'</div></th><th style="text-align: center; vertical-align: middle; "><div class="th-inner ">'+ret.msg.time_list[i]['number']+'</div></th><th style="text-align: center; vertical-align: middle; "><div class="th-inner ">'+ret.msg.time_list[i]['cartype1']+'</div></th><th style="text-align: center; vertical-align: middle; "><div class="th-inner ">'+ret.msg.time_list[i]['cartype2']+'</div></th><th style="text-align: center; vertical-align: middle; "><div class="th-inner " id="status'+i+'">'+ret.msg.time_list[i]['status']+'</div></th><th style="text-align: center; vertical-align: middle; ">  <div class="th-inner">'
                                                if(ret.msg.time_list[i]['time_status']){
                                                    if(ret.msg.time_list[i]['status'] == '开启中'){
                                                        content +='<button class="btn btn-success" id="time_status'+i+'" onclick="operation('+ret.msg.id+','+i+')">'+ret.msg.time_list[i]['time_status']+'</button><button class="btn btn-success add_paike" data-url="coachconfig/paikesc/add_paike?id='+i+'&coach_id='+ret.msg.id+'&day='+ret.msg.day+'" style="margin-left:5px" id="paike'+i+'" onclick="paike('+ret.msg.id+','+i+')">平台排课</button></div></th></tr>';
                                                    }else{
                                                        content +='<button class="btn btn-success" id="time_status'+i+'" onclick="operation('+ret.msg.id+','+i+')">'+ret.msg.time_list[i]['time_status']+'</button></div></th></tr>';
                                                    }
                                                }else{
                                                    content +='当前时间已过时';
                                                }
                                            }
                                            var tbody=document.getElementById("item")
                                            tbody.innerHTML= content
                                            // console.log(content)
                                            // Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                            //如果需要阻止成功提示，则必须使用return false;
                                            //return false;
                                        },
                                        error: function (data, ret) {
                                            // console.log(data, ret);
                                            // Layer.alert(ret.msg);
                                            return false;
                                        }
                                    }
                                ], formatter: Table.api.formatter.operate
                            }
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table);

                $(document).on('click','.add_paike', function (event) {
                    var url = $(this).attr('data-url');
                    if(!url) return false;
                    var msg = $(this).attr('data-title');
                    var width = $(this).attr('data-width');
                    var height = $(this).attr('data-height');
                    var area = [$(window).width() > 800 ? (width?width:'800px') : '95%', $(window).height() > 600 ? (height?height:'600px') : '95%'];
                    var options = {
                        shadeClose: false,
                        shade: [0.3, '#393D49'],
                        area: area,
                        callback:function(value){
                            // console.log(value)
                            // Fast.api.close(data);
                            // CallBackFun(value.id, value.name);//在回调函数里可以调用你的业务代码实现前端的各种逻辑和效果
                        }
                    };
                    // console.log(url,msg,options)
                    Fast.api.open(url,msg,options);
                });

                
            },
            
        },
        
    };

    return Controller;
});
