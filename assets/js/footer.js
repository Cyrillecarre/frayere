document.addEventListener('DOMContentLoaded', function() {
    var mentionsLegalesBtn = document.getElementById('mentionsLegalesBtn');
    var mentionsLegalesPopup = document.getElementById('mentionsLegalesPopup');
    var closePopupBtn = document.getElementById('closePopupBtn');
    var closePopupBtn1 = document.getElementById('closePopupBtn1');

    mentionsLegalesBtn.addEventListener('click', function() {
        mentionsLegalesPopup.style.display = 'block';
    });

    closePopupBtn.addEventListener('click', function() {
        mentionsLegalesPopup.style.display = 'none';
    });

    closePopupBtn1.addEventListener('click', function() {
        mentionsLegalesPopup.style.display = 'none';
    });
});
