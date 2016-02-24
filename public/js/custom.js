function saveArtistActivity(type, mediaId, contestId) {
    $.ajax({
        type: 'GET',
        url: baseURL + '/actions.php',
        data: 'action=artistActivityLog&type=' + type + '&mediaId=' + mediaId + '&portfolioId=' + '0' + '&contestId=' + contestId,
        success: function (data)
        {
//            alert('worked');
        }
    });
}