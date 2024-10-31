jQuery(document).ready(function($){
	let create_feed = $('#create_feed');
    let spinner = $('#loader');
	create_feed.submit(function(event){
		event.preventDefault();
        let file_name = $('#file_name').val();
        let file_type = $('#file_type').val();
        let formData = new FormData();
        formData.append('action','create_feed');
        formData.append('file_name',file_name);
        formData.append('file_type',file_type);
        spinner.show();
                $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    spinner.hide();
                    $('#msg').fadeIn().html(data);  
                          setTimeout(function(){  
                               $('#msg').fadeOut("Slow");  
                          }, 4000); 
                	
                      
                    // Ajax call completed successfully
                  //  alert("Form Submited Successfully");
                },
                error: function(data) {
                      spinner.hide();
                    // Some error in ajax call
                    $('#msg').fadeIn().html(data);  
                          setTimeout(function(){  
                               $('#msg').fadeOut("Slow");  
                          }, 4000); 
                }
            });
      
	});
});

