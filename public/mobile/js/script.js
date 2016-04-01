$(document).ready(function(){
    $('#yourIntoContestModal').on('show.bs.modal', function (e) {
        $('span.contest-name').text($(e.relatedTarget).data('contest-name'));
        $('img.down-image').attr('src', $(e.relatedTarget).data('img-src'));
    });
    
    $('.show-round-btn').on('click', function(){
        var displaySection = $(this).data('show-section');        
        if ($(displaySection).length) {
            $('div[id=r1Section], div[id=r2Section], div[id=r3Section], div[id=r4Section], div[id=r5Section], div[id=r6Section]').hide();
            $(displaySection).show();
             $("html, body").animate({scrollTop: $(displaySection).offset().top - 100}, "slow");
        }
    });
    
    $('div.bracket-rankings img.contest-img.pointer').on('click', function(){
        var mediaId = $(this).data('mediaid');
        if (mediaId.length) {
           window.location.href = baseURL+'/photo/'+mediaId+'/photo.html'; 
        }
    });
});