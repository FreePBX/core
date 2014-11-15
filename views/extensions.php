<div class="ext-container">
  <form class="popover-form fpbx-submit" name="frm_extensions" action="<?php echo $action?>" method="post" data-fpbx-delete="config.php?display=extensions&amp;extdisplay=<?php echo $_REQUEST['extdisplay'] ?>&amp;action=del" role="form">
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
      <?php $hiddens='';$c=1;foreach(array_keys($middle) as $category) { ?>
        <?php $active = ($c == 1); ?>
        <div id="<?php echo strtolower($category)?>" class="info-pane <?php echo $active ? '' : 'hidden'?>">
          <div class="container-fluid">
            <?php foreach ( array_keys($middle[$category]) as $order ) {
                    foreach( array_keys($middle[$category][$order]) as $section) {
                      echo "<div class='section-title'><h3>".$section."</h3></div><div class='section'>";
                      foreach ( array_keys($middle[$category][$order][$section]) as $sortorder ) {
                        if(!is_array($middle[$category][$order][$section][$sortorder])) {
                          continue;
                        }
                        $keys = array_keys($middle[$category][$order][$section][$sortorder]);
                        foreach ( $keys as $idx ) {
                          $elem = $middle[$category][$order][$section][$sortorder][$idx];
                          $helptext = $elem->get('helptext');
                          $h = !empty($helptext) ? '<i class="fa fa-question-circle" data-id="'.$elem->get('_elemname').'"></i>' : '';
                          $html = '<div class="form-group"><div class="col-md-3"><label class="control-label" for="'.$elem->get('_elemname').'">'.$elem->get('prompttext')." ".$h.'</label></div><div class="col-md-9">'.$elem->get('html_input').'</div></div>';
                          $pure = $elem->get('helptext').$elem->get('prompttext').$elem->get('html_input');
                          $help = "";
                          if(!empty($pure)) {
                            $help = !empty($helptext) ? '<span id="'.$elem->get('_elemname').'-help" class="help-block">'.$elem->get('helptext').'</span>' : '';
                          } else {
                            $_html = $elem->get('_html');
                            $html_text = $elem->get('html_text');
                            if(!empty($_html)) {
                              $html = $_html;
                            } elseif(!empty($html_text)) {
                              $html = $html_text;
                            } else {
                              $html = "";
                            }
                          }
                          if($elem->get('type') != "hidden") {
                            if(!empty($html)) {
                              echo '<div class="row"><div class="col-md-12 element-container" data-id="'.$elem->get('_elemname').'">';
                              echo '<div class="row parent" data-id="'.$elem->get('_elemname').'"><div class="col-md-12">'.$html .'</div></div>';
                              if(!empty($help)) {
                                echo '<div class="row"><div class="col-md-12">'.$help.'</div></div>';
                              }
                              echo '</div></div>';
                            }
                          } else {
                            $hiddens .= $html;
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
    <?php echo $hiddens;?>
  </form>
</div>
<script>
  var theForm = document.frm_extensions;
  <?php
  $formname = "frm_extensions";
  $htmlout = '';
  if ( is_array($jsfuncs) ) {
    foreach ( array_keys($jsfuncs) as $function ) {
      // Functions
      if ( $function == 'onsubmit()' ) {
        $htmlout .= "function ".$formname."_onsubmit(theForm) {\n";
      } else {
        $htmlout .= "function ".$formname."_$function {\n";
      }
      foreach ( array_keys($jsfuncs[$function]) as $sortorder ) {
        foreach ( array_keys($jsfuncs[$function][$sortorder]) as $idx ) {
          $func = $jsfuncs[$function][$sortorder][$idx];
          $htmlout .= ( isset($func) ) ? "$func" : '';
        }
      }
      if ( $function == 'onsubmit()' ) {
        $htmlout .= "\treturn true;\n";
      }
      $htmlout .= "}\n";
      echo $htmlout;
    }
  }?>
  $("form").submit(function() {
    return frm_extensions_onsubmit(this);
  });
</script>
