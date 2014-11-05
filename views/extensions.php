<style>
.ext-container {
  width: 75%;
  float: left;
}
.ext-container .display {
  border-bottom: 1px solid #ddd;
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
  padding: 5px;
  margin-right: -1px;
}
.ext-container .row {
  margin-bottom: 5px;
}
.ext-container .help-block {
  display:none;
  background-color: rgba(242, 242, 242, 0.47);
  padding: 5px;
  border-radius: 5px;
}
.section-title {
  float: left;
  padding-bottom: 5px;
}
.section-title h3 {
  margin-bottom: 0px;
}
.section {
  clear: both;
  border: 1px solid #ddd;
  padding: 5px;
  border-radius: 5px;
}
.fa.fa-question-circle {
  color: #0070a3;
  cursor: pointer;
  height: 10px;
  width: 10px;
  font-size: small;
  vertical-align: super;
  display: inline-block;
  text-align: center;
  margin: 2px;
  padding-right: 1.5px;
  padding-top: 1px;
  padding-left: 1px;
  font-weight: normal;
}
.item-holder {
  padding-left: 10px;
  padding-right: 10px;
}
.item {
  background-color: transparent;
  overflow: auto;
  padding-top: 1px;
}
.item.active {
  background-color: rgba(242, 242, 242, 0.47);
}
</style>
<div class="ext-container">
  <form class="popover-form fpbx-submit" name="frm_extensions" action="" method="post" data-fpbx-delete="config.php?type=danger&display=extensions&extdisplay=<?php echo $_REQUEST['extdisplay'] ?>&action=del" role="form">
    <?php foreach ( array_keys($top) as $sortorder ) {
            foreach ( array_keys($top[$sortorder]) as $idx ) {
              $elem = $top[$sortorder][$idx];
              $html = $elem->generatehtml();
              echo $html . (!preg_match('/type="hidden"/',$html) ? "<br/>" : "");
            }
          } ?>
    <ul class="nav nav-tabs" role="tablist">
      <?php $c=1;foreach(array_keys($middle) as $category) { ?>
        <?php $active = ($c == 1); ?>
        <li data-name="<?php echo strtolower($category)?>" class="change-tab <?php echo $active ? 'active' : ''?>"><a href="#<?php echo strtolower($category)?>"><?php echo ucfirst($category)?></a></li>
      <?php $c++;} ?>
    </ul>

    <div class="display">
      <?php $c=1;foreach(array_keys($middle) as $category) { ?>
        <?php $active = ($c == 1); ?>
        <div id="<?php echo strtolower($category)?>" class="info-pane <?php echo $active ? '' : 'hidden'?>">
          <div class="container-fluid">
            <?php foreach ( array_keys($middle[$category]) as $order ) {
                    foreach( array_keys($middle[$category][$order]) as $section) {
                      echo "<div class='section-title'><h3>".$section."</h3></div><div class='section'>";
                      foreach ( array_keys($middle[$category][$order][$section]) as $sortorder ) {
                        foreach ( array_keys($middle[$category][$order][$section][$sortorder]) as $idx ) {
                          $elem = $middle[$category][$order][$section][$sortorder][$idx];
                          $h = !empty($elem->helptext) ? '<i class="fa fa-question-circle"></i>' : '';
                          $html = '<div class="form-group"><div class="col-md-3"><label for="'.$elem->_elemname.'">'.$elem->prompttext." ".$h.'</label></div><div class="col-md-9">'.$elem->html_input.'</div></div>';
                          $pure = $elem->helptext.$elem->prompttext.$elem->html_input;
                          $help = "";
                          if(!empty($pure)) {
                            $help = !empty($elem->helptext) ? '<div class="col-md-12"><span class="help-block">'.$elem->helptext.'</span></div>' : '';
                          } else {
                            if(!empty($elem->_html)) {
                              $html = '<div class="col-md-12">'.$elem->_html.'</div>';
                            } elseif(!empty($elem->html_text)) {
                              $html = '<div class="col-md-12">'.$elem->html_text.'</div>';
                            } else {
                              $html = "";
                            }
                          }
                          if(!empty($html)) {
                            echo '<div class="row item-holder"><div class="item">'.$html .''.$help.'</div></div>';
                          }
                        }
                      }
                      echo "</div>";
                    }
                  } ?>
            </div>
        </div>
      <?php $c++;} ?>
    </div>
  </form>
</div>
<script>
  var loc = window.location.hash.replace("#", "");
  if(loc !== "" && $("#" + loc + ".info-pane").length > 0) {
    $(".info-pane").addClass("hidden");
    $(".change-tab").removeClass("active");
    $("#" + loc + ".info-pane").removeClass("hidden");
    $(".change-tab[data-name='" + loc + "']").addClass("active");
  }
  $(".change-tab").click(function(event) {
    var pos = document.body.scrollTop;
    if($(this).hasClass("active")) {
      event.stopPropagation();
      event.preventDefault();
      return true;
    }
    $(".info-pane").addClass("hidden");
    $(".change-tab").removeClass("active");
    $(this).addClass("active");
    var id = $(this).data("name");
    $("#" + id).removeClass("hidden");
    location.hash = id;
    document.body.scrollTop = document.documentElement.scrollTop = pos;
    event.stopPropagation();
    event.preventDefault();
  });
  $(".ext-container .fa.fa-question-circle").hover(function(){
    var el = $(this).parents(".row").find(".help-block");
    var el2 = $(this).parents(".row").find(".item");
    el.fadeIn("fast").css("display", "block");
    el2.addClass("active");
  }, function(){
    var el = $(this).parents(".row").find(".help-block");
    var input = $(this).parents(".row").find(".form-control");
    var el2 = $(this).parents(".row").find(".item");
    if(input.length && !input.is(":focus")) {
      el.fadeOut("fast");
      el2.removeClass("active");
    }
  })
  $(".ext-container input, .ext-container select, .ext-container textarea").focus(function() {
    var el = $(this).parents(".row").find(".help-block");
    var el2 = $(this).parents(".row").find(".item");
    el.fadeIn("fast").css("display", "block");
    el2.addClass("active");
  });
  $(".ext-container input, .ext-container select, .ext-container textarea").blur(function() {
    var el = $(this).parents(".row").find(".help-block");
    var el2 = $(this).parents(".row").find(".item");
    el.fadeOut("fast");
    el2.removeClass("active");
  });
</script>
