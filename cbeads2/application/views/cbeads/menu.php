<?php header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
      header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past to force this page to be reloaded every time?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">

    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />
    <script src="<?php echo base_url(); ?>libs/jquery-1.4.2.min.js" type="text/javascript"></script>

    <style type="text/css"> 

        


    </style>
</head>

<body class="cbeads_menu">

<div id="menu-div">

    <div style='font-size: 1em; font-family: verdana; margin: 10px 10px;'>
         <?php echo '<strong>You are logged in as: </strong>'.$username; ?>
    </div>

    <table cellspacing="0" cellpadding="0" id="menu" class="click-menu">

    <?php foreach($groups as $group):?>

        <tr id="group_row_<?php echo $group['group_id']; ?>">
            <td>

            <?php if($group['show_group_header']): ?>
            
                <div id="<?php echo $group['group_id']; ?>" class="group_header"><?php echo $group['name'];?><img src='<?php echo base_url().'cbeads/css/arrow1.gif';?>' width="11" height="11" alt=""></div> 
                <div id="group_items_<?php echo $group['group_id']; ?>" class="group_items">
                <?php foreach($group['items'] as $item):?>
                    <div class="group_item">
                        <a href="<?php echo $item['url']; ?>" target="content">
                            <div>
                                <img src="<?php echo base_url().'cbeads/css/arrow2.gif';?>" width="14" height="9" alt=""><?php echo $item['name'];?>
                            </div>
                        </a>
                    </div>
                <?php endforeach;?>
                </div>
                
            <?php else: ?>
                
                <div id="group_items_<?php echo $group['group_id']; ?>" class="group_items_free">
                <?php foreach($group['items'] as $item):?>
                    <div class="group_item">
                        <a href="<?php echo $item['url']; ?>" target="content">
                            <div>
                                <img src="<?php echo base_url().'cbeads/css/arrow2.gif';?>" width="14" height="9" alt=""><?php echo $item['name'];?>
                            </div>
                        </a>
                    </div>
                <?php endforeach;?>
                </div><br>
                
            <?php endif; ?>
            
            </td>
        </tr>
        <tr><td height="2"></td></tr>
        </tr>

    <?php endforeach;?>

    </table>
</div>
<div id="debug"></div>
<!---<div id="extra-div" style="height:40px;"></div>--->

<script type="text/javascript">

    $(document).ready(function(){
        //var clickMenu1 = new ClickShowHideMenu("menu");
        //clickMenu1.init();
        
        /*if($.browser.msie && $.browser.version < 8){
            //$("#extra-div").html("<i style='font-size: 0.7em;'>This site is best viewed with: Chrome, Opera, Firefox or Internet Explorer 8</i>");
            //$(window).resize(do_resize);
        
            //do_resize();
        }
        
        function do_resize()
        {
            var wh = $(window).height();
            var eh = $("#extra-div").height();
            //alert(wh);
            $("#menu-div").height(wh - eh);
        }*/
        
        // Hide all groups automatically.
        $('.group_items').each(function(){
            $(this).hide();
        });
        // Clicking on a header will toggle the group items.
        $('.group_header').click(clicked_group_header);
        // Hovering over a header will change its class, but only when the group isn't expanded.
        $('.group_header').hover(function(){
            $('#group_items_' + this.id).css('display');
            //$("#group_items_" + this.id).slideToggle('500');
            //if($("#group_items_" + this.id).css('display') == 'none')
                $(this).removeClass('group_header').addClass('group_header-hover');
        },
        function(){
            //if($("#group_items_" + this.id).css('display') == 'none')
                $(this).removeClass('group_header-hover').addClass('group_header');
        });
        // Hovering over a menu item will change its class so the user can clearly see what item will be clicked.
        $('.group_item').hover(function(){
            $(this).addClass('group_item-hover');
        },
        function(){
            $(this).removeClass('group_item-hover');
        });
        
    });

    function clicked_group_header()
    {
        var id = this.id;
        var n = $("#group_items_" + id).queue("fx");
        if(n.length > 0) return;                    // Don't do anything if an animation is running. (no queueing)
        $(this).addClass('group_header_open');  
        var speed = 500;
        if($.browser.msie && $.browser.version < 9) speed = 0;   // IE has an issue. When the menu group is closed it expands the div element fully near the end of the animation and then hides it. So no animation for IE users.
        $("#group_items_" + this.id).slideToggle(speed, function(){
            if($(this).css('display') == 'none') $('#' + id).removeClass('group_header_open');
            //scroll_to_group(id);
        });
        
    }
    
    
    function show(group)
    {
        //var n = $("#group_items_" + group).queue("fx");
        //if(n.length > 0) return;
        if($("#group_items_" + group).css('display') != 'none') return;     // Nothing to do if already open.
        var speed = 500;
        if($.browser.msie) speed = 0;   // IE has an issue. When the menu group is closed it expands the div element fully near the end of the animation and then hides it. So no animation for IE users.
        $("#group_items_" + group).stop(true).slideToggle(speed, function(){
            if($(this).css('display') == 'none') $('#' + group).removeClass('group_header_open');
            //scroll_to_group(group);
        });
    }
    
    function hide(group)
    {
        //var n = $("#group_items_" + group).queue("fx");
        //if(n.length > 0) return;
        if($("#group_items_" + group).css('display') != 'block') return;        // Nothing to do if already closed.
        var speed = 500;
        if($.browser.msie) speed = 0;   // IE has an issue. When the menu group is closed it expands the div element fully near the end of the animation and then hides it. So no animation for IE users.
        $("#group_items_" + group).stop(true).slideToggle(speed, function(){
            if($(this).css('display') == 'none') $('#' + group).removeClass('group_header_open');
            //scroll_to_group(group);
        });
    }
    
    function test(value)
    {
        // See if there is a menu group with this id.
        if(!document.getElementById(value)) return;
        var obj = $("#" + value);
        obj.trigger('click');
        
        var winheight = $(window).height();
        var docheight = $(document).height();
        
        var pos = obj.position();
        var html = "Position is: " + pos.left + " : " + pos.top + "<br>";
        html += "Window height is: " + winheight + "<br>";
        html += "Document height is: " + docheight + '<br>';
        //$("#debug").html(html);
    }
    
    // Might need to scroll to make the whole group visible.
    function scroll_to_group(value)
    {
        // if(!document.getElementById(value)) return;
        // var row = $('#group_row_' + value);
        // var height = row.outerHeight();
        
        // var winheight = $(window).height();
        // var docheight = $(document).height();
        
        // var pos = row.position();
        // var html = "Position is: " + pos.left + " : " + pos.top + "<br>";
        // html += "Height is: " + height + "<br>";
        // html += "Window height is: " + winheight + "<br>";
        // html += "Document height is: " + docheight + '<br>';
        // $("#debug").html(html);
        
        
        // $(window).scrollTop(pos.top);
    }
    
    
    
    function show2(group)
    {
        $("#group_items_" + group).slideDown(0);
        $('#' + group).addClass('group_header_open');
    }
    
    function hide2(group)
    {
        //var n = $("#group_items_" + group).queue("fx");
       // if(n.length > 0) return;
        $("#group_items_" + group).slideUp(0);
        $('#' + group).removeClass('group_header_open');
    }
    

</script>


</body>
