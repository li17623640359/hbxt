//产品图
$('.mod_01').css('width',document.body.offsetWidth);
if(document.getElementById("slide_01")){
    var slide_01 = new ScrollPic();
    slide_01.scrollContId   = "slide_01"; //内容容器ID
    slide_01.dotListId      = "slide_01_dot";//点列表ID
    slide_01.dotOnClassName = "selected";
    slide_01.frameWidth     = document.body.offsetWidth;
    slide_01.pageWidth      = document.body.offsetWidth;
    slide_01.upright        = false;
    slide_01.speed          = 10;
    slide_01.space          = 30;
    slide_01.initialize(); //初始化
}// JavaScript Document