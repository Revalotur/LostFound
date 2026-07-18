// assets/js/location-picker.js
// Shared map picker + location autocomplete (Leaflet + OpenStreetMap / Nominatim)
// Mirip pengalaman Google Maps Places tanpa API key.

function initLocationPicker(options) {
    var opts = options || {};
    var mapElId        = opts.mapElId        || 'map-picker';
    var searchInputEl  = opts.searchInputEl  || document.querySelector('input[name="location"]');
    var latInput       = document.getElementById(opts.latInputId  || 'lat');
    var lngInput       = document.getElementById(opts.lngInputId  || 'lng');
    var defaultLat     = typeof opts.defaultLat !== 'undefined' ? opts.defaultLat : -6.200000;
    var defaultLng     = typeof opts.defaultLng !== 'undefined' ? opts.defaultLng : 106.816666;
    var defaultZoom    = opts.defaultZoom || 13;

    if (!searchInputEl) return;

    var map = L.map(mapElId).setView([defaultLat, defaultLng], defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    setTimeout(function () { map.invalidateSize(); }, 200);

    var marker;
    if (typeof defaultLat === 'number' && typeof defaultLng === 'number' &&
        defaultLat !== -6.200000 && defaultLng !== 106.816666) {
        marker = L.marker([defaultLat, defaultLng]).addTo(map);
    }

    // ---- Suggestions container ----
    var suggestionList = document.createElement('ul');
    suggestionList.className = 'location-suggestions';
    searchInputEl.parentNode.insertBefore(suggestionList, searchInputEl.nextSibling);

    var activeIndex = -1;
    var currentResults = [];
    var cache = {};

    function clearSuggestions() {
        suggestionList.innerHTML = '';
        suggestionList.style.display = 'none';
        activeIndex = -1;
        currentResults = [];
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function highlight(text, query) {
        var safe = escapeHtml(text);
        if (!query) return safe;
        var q = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return safe.replace(new RegExp('(' + q + ')', 'ig'), '<mark>$1</mark>');
    }

    function showLoading() {
        suggestionList.innerHTML = '<li class="loc-loading"><i data-lucide="loader" style="width:16px;"></i> Mencari lokasi...</li>';
        suggestionList.style.display = 'block';
        if (window.lucide) lucide.createIcons();
    }

    function showEmpty() {
        suggestionList.innerHTML = '<li class="loc-empty">Tidak ada hasil. Coba kata kunci lain.</li>';
        suggestionList.style.display = 'block';
    }

    function renderSuggestions(results, query) {
        suggestionList.innerHTML = '';
        if (!results || !results.length) { showEmpty(); return; }

        results.forEach(function (result, idx) {
            var li = document.createElement('li');
            li.dataset.index = idx;
            li.innerHTML =
                '<span class="loc-icon"><i data-lucide="map-pin" style="width:16px;"></i></span>' +
                '<span class="loc-text">' + highlight(result.display_name, query) + '</span>';
            li.addEventListener('click', function () { selectLocation(result); });
            suggestionList.appendChild(li);
        });

        suggestionList.style.display = 'block';
        activeIndex = -1;
        if (window.lucide) lucide.createIcons();
    }

    function setActive(idx) {
        var items = suggestionList.querySelectorAll('li[data-index]');
        items.forEach(function (el) { el.classList.remove('active'); });
        if (idx >= 0 && idx < items.length) {
            items[idx].classList.add('active');
            items[idx].scrollIntoView({ block: 'nearest' });
        }
        activeIndex = idx;
    }

    function selectLocation(result) {
        var lat = parseFloat(result.lat);
        var lng = parseFloat(result.lon);
        if (isNaN(lat) || isNaN(lng)) return;

        map.setView([lat, lng], 16);
        if (marker) { marker.setLatLng([lat, lng]); }
        else { marker = L.marker([lat, lng]).addTo(map); }

        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
        searchInputEl.value = result.display_name;
        clearSuggestions();
    }

    var searchTimer;
    function searchLocation(query) {
        if (!query || query.length < 3) { clearSuggestions(); return; }
        if (cache[query.toLowerCase()]) {
            renderSuggestions(cache[query.toLowerCase()], query);
            return;
        }
        showLoading();
        fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&accept-language=id&q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            cache[query.toLowerCase()] = data;
            renderSuggestions(data, query);
        })
        .catch(function () { showEmpty(); });
    }

    searchInputEl.addEventListener('input', function (e) {
        var val = e.target.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { searchLocation(val); }, 350);
    });

    searchInputEl.addEventListener('keydown', function (e) {
        var items = suggestionList.querySelectorAll('li[data-index]');
        if (suggestionList.style.display === 'none' || items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive((activeIndex + 1) % items.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive((activeIndex - 1 + items.length) % items.length);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            if (currentResults[activeIndex]) selectLocation(currentResults[activeIndex]);
        } else if (e.key === 'Escape') {
            clearSuggestions();
        }
    });

    // store last results for keyboard nav
    var _origRender = renderSuggestions;
    renderSuggestions = function (results, query) {
        currentResults = results || [];
        _origRender(results, query);
    };

    document.addEventListener('click', function (e) {
        if (!suggestionList.contains(e.target) && e.target !== searchInputEl) {
            clearSuggestions();
        }
    });

    map.on('click', function (e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        if (marker) { marker.setLatLng(e.latlng); }
        else { marker = L.marker(e.latlng).addTo(map); }
        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;
    });

    // Try to center on user's geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            var ulat = pos.coords.latitude;
            var ulng = pos.coords.longitude;
            if (!(typeof defaultLat === 'number' && defaultLat !== -6.200000)) {
                map.setView([ulat, ulng], 13);
            }
        });
    }
}
