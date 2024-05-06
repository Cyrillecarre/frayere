var map = L.map('map').setView([47.0778847, 0.5099637], 13);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

L.marker([47.0778847, 0.5099637]).addTo(map)
    .bindPopup('La Frayère')
    .openPopup();

    function burger(){
        const burgerMenu = document.getElementById('burger-menu');
        const navList = document.querySelectorAll('.navListe');
          
          burgerMenu.addEventListener('click', function() {
              navList.forEach(item => {
                  item.classList.toggle('show');
              });
            });
          }
          burger();