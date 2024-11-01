/**
 * Created by Paul on 2016-07-04.
 */

var tagged;// var to hold the tag being saved
var spinner;

jQuery(document).ready(function($) {
    if ($('.admin-bar').length > 0) {
        load_tag_button();
    }

    // if preload set, set tags, else fetch tags via ajax
    if (designtag_vars.wcpt_preload == 1) {
        reset_tags();
    } else {
        get_all_designtags(jQuery('.tag_autoload'),false);
    }


        //check to see if featured products shortcode has been applied, then render html
        if(jQuery('div#wcpt-featured-products-inner').length) {
            jQuery('div#wcpt-featured-products').append(jQuery('div#wcpt-featured-products-inner').html());
        }



    jQuery('.wcpt-article').mouseenter(function() {
        jQuery(this).children('.wcpt-post-title').fadeIn();
    });
    jQuery('.wcpt-article').mouseleave(function() {
        jQuery(this).children('.wcpt-post-title').fadeOut();
    });


    jQuery(document).on('click','#tag_form #tag_save',function(e) {
        var that = this;
        tagged.image_tag.set_original(jQuery('#chk_original:checked').val());
        tagged.image_tag.set_description(jQuery('#txt-description').val());
        console.log('saving tag for ' + tagged.tagged_obj.id);
        jQuery(this).prop("disabled",true);
        jQuery(this).val("saving...");
        tagged.save(function(result) {
            if (result.successful) {
                jQuery(that).val("Saved successfully");
                jQuery('#tag_form #tag_cancel').hide();
                jQuery('#tag_form').delay(500).fadeOut(200);
                jQuery('#tag_form #tag_cancel').show();
                var tag_top = Math.round(tagged.image_tag.pos_y-tagged.image_tag.height/2)+'px';
                var tag_left =  Math.round(tagged.image_tag.pos_x- tagged.image_tag.width/2)+'px';
                tagged.image_tag.ratio_x = result.data.tag_position.ratio_x;
                tagged.image_tag.ratio_y = result.data.tag_position.ratio_y;
                tagged.image_tag.pos_x = result.data.tag_position.tag_x;
                tagged.image_tag.pos_y = result.data.tag_position.tag_y;
                tagged.image_tag.id = result.data.tag_id;
                tagged.set_html(result.data.html);
                //jQuery(result.data.html).insertAfter('li.designtag');
                jQuery('body').append(result.data.html);
                reset_tags();

            } else {
                alert('Save failed');
                jQuery(that).val("Save");
                jQuery(this).prop("disabled",false);
            }

        });
    });

    jQuery(document).on('click','#tag_form #tag_cancel',function(e) {

        jQuery('#tag_form').fadeOut(200);
    });

    jQuery(document).on('click','#tag-products-layer .close-modal.x-black',function(e) {

        $('#tag-products-layer').animate({left:'-20%'},1000, function() {
            $('#tagtool-tab').show();
        });

        jQuery('body').removeClass('tagtool');
        reset_tags();

    });



    jQuery(document).on('click','.tagtool-section.products', function(e){
        jQuery('.tagtool-product-list').toggle();
        jQuery('.tagtool-user-list').toggle();
    });

    jQuery('#tagtool-tab').click(function(e) {


        e.preventDefault();
        jQuery('body:not(.tagtool)').addClass('tagtool');

        if ($('#tag-products-layer').length) {
            $('#tag-products-layer').show();
            $('#tag-products-layer').animate({left:'0'},1000);
            $('#tagtool-tab').hide();
            reset_tags();
            init_tagging();
        } else {
            load_tagging_html();
        }

       
    });

    jQuery(document).on('click','#tag-products-layer a.page-numbers', function(e) {
        e.preventDefault();
        var page = '';
        var cats = $('#tag-products-layer #cat').val();
        if (jQuery(this).is('a.page-numbers')) {
            var url = jQuery(this).attr('href');
            page = getUrlParameter(url,'product-page');
        }
        var text = $('#tag-search').val();
        get_products(cats,text,page);
    });


    jQuery('#tag-products-layer #tagtool-filter').click(function () {
        var cats = $('#tag-products-layer #cat').val();
        var text = $('#tag-products-layer #tag-search').val();
        get_products(cats,text,1);
    });

    function load_tagging_html(page) {
        $.ajax({
            url:designtag_vars.ajax_url,
            data: {
                action: 'wcpt_tag_panel_output',
                'product-page' : page
            }
        }).done(function(data) {
            $('body').append(data);
            jQuery('#tag-products-layer').show();
            reset_tags();
            init_draggable();
            init_droppable();
            jQuery('.product-simple img').each(function(){var redraw = jQuery(this).offset().height;});
            jQuery(document).on('mouseenter','.draggable', function() {
                if (!jQuery(this).hasClass('ui-draggable')) {
                    init_draggable();
                }
            });
        }).fail(function(jqXHR,errorStatus,errorThrown) {
            alert('There was an error loading the tool: ' + errorStatus + ' : ' + errorThrown);
        });
    }

    function get_products(cats,text,page) {
        $.ajax({
            url:designtag_vars.ajax_url,
            data: {
                action: 'wcpt_tagtool_product_search',
                'product-page' : page,
                'cats': cats,
                'tag-search':text
            }
        }).done(function(data) {
            $('#tagtool-product-results').empty();
            $('#tagtool-product-results').append(data);
            jQuery('#tag-products-layer').show();
            reset_tags();
            init_draggable();
            init_droppable();
            jQuery('.product-simple img').each(function(){var redraw = jQuery(this).offset().height;});
            jQuery(document).on('mouseenter','.draggable', function() {
                if (!jQuery(this).hasClass('ui-draggable')) {
                    init_draggable();
                }
            });
        }).fail(function(jqXHR,errorStatus,errorThrown) {
            alert('There was an error loading the tool: ' + errorStatus + ' : ' + errorThrown);
        });
    }

    jQuery(document).on('mouseenter click','li.designtag', function(e) {
        var zin = 9;
        if (jQuery(this).attr('id').match('_z')) {
           zin = 1501;
        }
        jQuery(this).css('z-index',zin);
        jQuery(this).children('.ft_msg').show();
        jQuery(this).children('i.designtag').hide();

        var client = "C_" + jQuery(this).data('author-login');
        var user = jQuery(this).data('author-name');
        var type = jQuery(this).data('type');
        var id = jQuery(this).data('id');
        var title = jQuery(this).data('title');
        if (designtag_vars.wcpt_ga == 1) {
            ga('send', 'event', 'Woocommerce Photo Tag', 'Tag View', title);
        }

        e.stopPropagation();
    });

    jQuery(document).on('mouseleave','li.designtag', function() {
        var zin = 8;
        if (jQuery(this).attr('id').match('_z')) {
            zin = 1501;
        }
        jQuery(this).children('.ft_msg').hide();
        jQuery(this).children('i.designtag').show();

        jQuery(this).css({'z-index':zin});
    });


    jQuery(document).on('click','.ft_msg a', function(e) {
        var title = jQuery(this).parents('li.designtag').data('title');
        ga('send', 'event', 'Woocommerce Photo Tag', 'Product View', title);
    });


    jQuery(document).on('click','#tag_form #chk_original',function() {
        //console.log('checkbox clicked');
        if (jQuery(this).prop('checked')) {
            jQuery('#tag_form i').addClass('designtag');
            jQuery('#tag_form i').removeClass('suggestion');
        } else {
            jQuery('#tag_form i').addClass('suggestion');
            jQuery('#tag_form i').removeClass('designtag');
        }
    });



    jQuery(window).resize(function() {

        reset_tags();
    });

    jQuery('.single-product .tabs a').click(function(e) {
        jQuery('li.designtag').hide();
        if (jQuery(this).attr('href') == '#tab-wcpt') {
            window.setTimeout('reset_tags()',100);
        }
    });

}); //end jquery doc ready

jQuery(document).ajaxStart(function() {
    spin(true);
});

jQuery(document).ajaxStop(function() {
    init_draggable();
    spin(false);
});

function reset_tags() {
    jQuery('li.designtag').each(function() {

            render_wcpt(jQuery(this));


    });
    enable_delete_tag();
}

function enable_delete_tag() {
    jQuery('.delete-tag').click(function(e){
        e.preventDefault();
        var tag_id = jQuery(this).data('tag_id');
        if (confirm('Are you sure?')) {
            delete_designtag(tag_id, function(result) {
                if (!result.success) {
                    alert(result.message);
                }

            });
        }
        e.stopPropagation();
    });
}

function init_tagging() {
    init_draggable();
    init_droppable();
}

function init_draggable() {
     jQuery( '.product-simple.draggable,.tagtool-user.draggable,.product-image-div' ).draggable({ opacity: 0.9,
        helper: "clone",
        cursor: "pointer",
         appendTo: "#main",
        cursorAt: { top: -5, left: -5 },
         start: function( event, ui ) {

            jQuery(ui.helper).addClass('ui-draggable-helper');
         }

    }); //end draggable
}

function init_droppable() {
    console.log('droppable initiated');
    jQuery( ".droppable" ).droppable({
        accept: ".product-simple.draggable,.tagtool-user.draggable",
        hoverClass: "ui-droppable-hover",
        tolerance: "pointer",
        drop: function( event, ui ) {
            var y = ui.offset.top - jQuery(this).offset().top - 5;
            var x = ui.offset.left - jQuery(this).offset().left - 5;
            console.log("dropped:" + ui.draggable.attr('id'));
            var entity_id = ui.draggable.attr('id');
            var entity_type = (entity_id.match(/user/)) ? 'user' : 'product';
            var entity;
            if (entity_type == 'product') {
                var product_id = entity_id;
                jQuery('#tag_form .fa-tag').show();
                jQuery('#tag_form .fa-user').hide();

                entity = new product(product_id,"");
                jQuery('#tag_form #tag_form_img').attr('src',jQuery('.product-simple.draggable#'+product_id+' img').attr('src'));


            }
            else if (entity_type=='user') {
                jQuery('#tag_form #tag_form_img').attr('src',jQuery('.tagtool-user.draggable#'+entity_id+' img').attr('src'));
                jQuery('#tag_form .fa-user').show();
                jQuery('#tag_form .fa-tag').hide();
                jQuery('#tag_form #original-product').hide();
                var user_id = ui.draggable.attr('id');
                jQuery('#tag_form .fa-user').show();
                jQuery('#tag_form .fa-tag').hide();
                var username = jQuery(ui.draggable.helper).find('.tagtool-user-link').text();
                entity = new user(user_id,username,'','');
            }
            var mytag = new tag('',x,y,20,20,20,20);

            tagged = new Designtag('',jQuery(this),mytag,entity,jQuery(this).data('wcpt_image_id'));
            //move the tag form to the body tag so that the x and y calcs work properly
            jQuery('body').prepend(jQuery('#tag_form'));
            jQuery('#tag_form').css({top:ui.offset.top,left:ui.offset.left});


            jQuery('#tag_form #tag_save').prop("disabled",false);
            jQuery('#tag_form #tag_save').val("Save");

            jQuery('#tag_form').fadeIn(200);
        },

    });//end droppable


}

function tag(id,pos_x,pos_y,ratio_x,ratio_y,width,height,original,html,description) {
    this.id = id;
    this.pos_x = pos_x;
    this.pos_y = pos_y;
    this.ratio_x = ratio_x;
    this.ratio_y = ratio_y
    this.width = width;
    this.height = height;
    this.original = original;
    this.description = description;
    this.html = html;
    this.set_original = function(val) {
        this.original = val;
    }
    this.set_description = function(val) {
        this.description = val;
    }
}

function product(prod_id,var_id) {
    this.id = prod_id;
    this.tagged_type = 'product';
    this.variation_id = var_id;
}

function Designtag(id,img,thetag,linked_obj,photo_id) {
    var that = this;
    this.tagged_image = img;
    this.photo_id = photo_id;
    this.image_tag = thetag;
    this.tagged_obj = linked_obj;
    this.id=id;
    this.set_html = function(html) {
        this.image_tag.html = html;
    }
    this.save = function(callback) {
        var posted = {
            action: 'wcpt_designtag_save',
            img_id : that.tagged_image.data('wcpt_image_id'),
            img_width: that.tagged_image.width(),
            img_height: that.tagged_image.height(),
            tag_x: that.image_tag.pos_x,
            tag_y: that.image_tag.pos_y,
            tag_width: that.image_tag.width,
            tag_height: that.image_tag.height,
            tag_original: that.image_tag.original,
            tag_id: that.image_tag.id,
            tag_description: that.image_tag.description,
            obj_type: that.tagged_obj.tagged_type,
            obj_id : that.tagged_obj.id,
            wcpt_meta_box_nonce: false,
            obj_var : (that.tagged_obj.tagged_type == 'product') ? that.tagged_obj.variation_id : ""
        };
        jQuery.ajax({
            url : designtag_vars.ajax_url,
            type : 'post',
            data : posted,
            datatype:JSON,

        }).done( function( result ) {
            return callback(result);
        }).fail(function(jqXHR,errorStatus,errorThrown) {
            alert('There was an error saving the tag: ' + errorStatus + ' : ' + errorThrown);
        });
    }
    this.fill = function(callback){
        jQuery.ajax({
            url : designtag_vars.ajax_url,
            type : 'post',
            data : {action:'wcpt_designtag_fill',id:that.id},
            datatype:JSON,

        }).done( function( result ) {
            callback(result);
        }).fail(function(jqXHR,errorStatus,errorThrown) {
            alert('There was an error filling the tag: ' + errorStatus + ' : ' + errorThrown);
        });
    }

}

function get_all_designtags(images,islightbox) {
    var ids = [];
    if (images) {
        images.each(function() {
            ids.push(jQuery(this).data('wcpt_image_id'));
        });
        var photo_ids = ids.join(',');
        var posted = {
            action: 'wcpt_get_all_designtags',
            photo_ids:  photo_ids,
            islightbox: islightbox
        };
        jQuery.ajax({
            url : designtag_vars.ajax_url,
            type : 'post',
            data : posted,
            datatype:JSON,

        }).done( function( result ) {
            tags = "";
            myresult = JSON.parse(result);
            var mytagobj;
            if (!myresult.no_results) {
                jQuery.each(myresult.tags,function() {
                    jQuery('body').append(this.html);
                });
                reset_tags();
                enable_delete_tag();

            } else {
                //console.log(mytags.error_message);
            }


        }).fail(function(jqXHR,errorStatus,errorThrown,img) {
            console.log('There was an error fetching the tags for ' + img + '. ' + errorStatus + ' : ' + errorThrown);
        });
    } else {
        console.log('Designtags: autoload requested but no image_id supplied for ' + img.attr('id'));
    }
}

function get_designtags(img,islightbox,callback) {

    var img_id = img.data('wcpt_image_id');
    console.log('doing ajax call for ' + img.attr('id'));
    if (img_id) {

        var posted = {
            action: 'wcpt_get_designtags',
            img_id :  img_id,
            islightbox: islightbox
        };
        jQuery.ajax({
            url : designtag_vars.ajax_url,
            type : 'post',
            data : posted,
            datatype:JSON,

        }).done( function( result ) {
            callback(result);
        }).fail(function(jqXHR,errorStatus,errorThrown,img) {
            console.log('There was an error fetching the tags for ' + img + '. ' + errorStatus + ' : ' + errorThrown);
        });
    } else {
        console.log('Designtags: autoload requested but no image_id supplied for ' + img.attr('id'));
    }

}

function render_wcpt(tag) {
    if (jQuery('.wcpt_img_' +  tag.data('tagged_image_id')).length && !(jQuery('.wcpt_img_' +  jQuery(tag).data('tagged_image_id')).parents().css('visibility') == 'hidden')) {
        var tag_img = jQuery('.wcpt_img_' +  tag.data('tagged_image_id'));
        var tag_top_no_px = jQuery(tag_img).offset().top + Math.round(parseFloat(tag.data('ratio_y'))* parseFloat(tag_img.height()) - parseFloat(tag.data('tag_height'))/2) ;
        var tag_top = tag_top_no_px +'px';
        var tag_left_no_px =  jQuery(tag_img).offset().left + Math.round(parseFloat(tag.data('ratio_x'))* parseFloat(tag_img.width())- parseFloat(tag.data('tag_width'))/2);
        var tag_left =  tag_left_no_px +'px';
        var overlap_y = (parseFloat(tag_img.height()) + parseFloat(tag_img.offset().top)) - (tag_top_no_px + 280);
        var overlap_x = (parseFloat(window.innerWidth) + parseFloat(tag_img.offset().left)) - (tag_left_no_px + 230);
        var overlap_x_px = overlap_x + 'px';
        var overlap_y_px = overlap_y + 'px';
        var zin = 8;

        if (overlap_y < 0) {
            tag.find('div.ft_msg').css('margin-top',overlap_y_px);
            //console.log(jQuery('li#tag_' + tag.image_tag.id).html());
        }
        if (overlap_x < 0) {
            tag.find('div.ft_msg').css('margin-left',overlap_x_px);
            //console.log(tag.image_tag.id);
        }
        tag.show();
        tag.css({'position':'absolute','display':'block','top':tag_top,'left':tag_left,'z-index':zin});
        //tag.show();
    } else {
        tag.hide();
    }

}

function delete_designtag(tag_id,callback) {
    var posted = {
        action: 'wcpt_delete_designtag',
        tag_id :  tag_id
    };
    jQuery.ajax({
        url : designtag_vars.ajax_url,
        type : 'post',
        data : posted,
        datatype:JSON,

    }).done( function( result ) {
        if (result.success) {
            jQuery('li#tag_'+result.tag_id + ',li#tag_'+result.tag_id+'_z').fadeOut(200).remove();
        }
        callback(result);
    }).fail(function(jqXHR,errorStatus,errorThrown) {
        alert('There was an error while communicating with the server ' + errorStatus + ' : ' + errorThrown);
    });
}

function spin(start) {
    if (start) {
        var opts = {
            lines: 13 // The number of lines to draw
            , length: 14 // The length of each line
            , width: 7 // The line thickness
            , radius: 21 // The radius of the inner circle
            , scale: 1 // Scales overall size of the spinner
            , corners: 1 // Corner roundness (0..1)
            , color: '#000' // #rgb or #rrggbb or array of colors
            , opacity: 0.25 // Opacity of the lines
            , rotate: 0 // The rotation offset
            , direction: 1 // 1: clockwise, -1: counterclockwise
            , speed: 1 // Rounds per second
            , trail: 60 // Afterglow percentage
            , fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
            , zIndex: 2e9 // The z-index (defaults to 2000000000)
            , className: 'spinner' // The CSS class to assign to the spinner
            , top: '50%' // Top position relative to parent
            , left: '50%' // Left position relative to parent
            , shadow: false // Whether to render a shadow
            , hwaccel: false // Whether to use hardware acceleration
            , position: 'absolute' // Element positioning
        }
        var target = document.getElementById('tag-products-layer')
        spinner = new Spinner(opts).spin(target);
    } else {

            spinner.stop();

    }


}

function load_tag_button() {
    var button_html = '<div id="tagtool-tab"><i class="fa fa-tags"></i></div>';
    jQuery('body').append(button_html);
}

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function remove_tags() {
    jQuery('.designtag').each(function() {
        jQuery(this).remove();
    });
}

function getUrlParameter(url,name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(url);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

/* end */




