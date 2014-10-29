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
</style>
<div class="ext-container">
  <form role="form">
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
        <li data-name="<?php echo $category?>" class="change-tab <?php echo $active ? 'active' : ''?>"><a href="#"><?php echo $category?></a></li>
      <?php $c++;} ?>
    </ul>

    <div class="display">
      <?php $c=1;foreach(array_keys($middle) as $category) { ?>
        <?php $active = ($c == 1); ?>
        <div id="<?php echo $category?>" class="info-pane <?php echo $active ? '' : 'hidden'?>">
          <div class="container-fluid">
            <?php foreach ( array_keys($middle[$category]) as $order ) {
                    foreach( array_keys($middle[$category][$order]) as $section) {
                      echo "<div class='section-title'><h3>".$section."</h3></div><div class='section'>";
                      foreach ( array_keys($middle[$category][$order][$section]) as $sortorder ) {
                        foreach ( array_keys($middle[$category][$order][$section][$sortorder]) as $idx ) {
                          $elem = $middle[$category][$order][$section][$sortorder][$idx];
                          $h = !empty($elem->helptext) ? '<i class="fa fa-question-circle"></i>' : '';
                          $html = '<div class="form-group"><div class="col-md-3" style="height: 34px;"><label for="'.$elem->_elemname.'">'.$elem->prompttext." ".$h.'</label></div><div class="col-md-9">'.$elem->html_input.'</div></div>';
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
                            echo '<div class="row">'.$html .''.$help.'</div>';
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
  $(".change-tab").click(function() {
    if($(this).hasClass("active")) {
      return true;
    }
    $(".info-pane").addClass("hidden");
    $(".change-tab").removeClass("active");
    $(this).addClass("active");
    var id = $(this).data("name");
    $("#" + id).removeClass("hidden");
  });
  $(".ext-container .fa.fa-question-circle").hover(function(){
    var el = $(this).parents(".row").find(".help-block");
    el.fadeIn("fast");
  }, function(){
    var el = $(this).parents(".row").find(".help-block");
    var input = $(this).parents(".row").find(".form-control");
    if(input.length && !input.is(":focus")) {
      el.fadeOut("fast");
    }
  })
  $(".ext-container input, .ext-container select, .ext-container textarea").focus(function() {
    var el = $(this).parents(".row").find(".help-block");
    el.fadeIn("fast");
  });
  $(".ext-container input, .ext-container select, .ext-container textarea").blur(function() {
    var el = $(this).parents(".row").find(".help-block");
    el.fadeOut("fast");
  });
</script>
