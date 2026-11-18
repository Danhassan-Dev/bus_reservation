// main.js - small interactions. Security checks always happen again on the server.
document.addEventListener('DOMContentLoaded', function () {

  // 1. Mobile menu
  var toggle = document.querySelector('.nav-toggle');
  var links  = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', function () { links.classList.toggle('open'); });
  }

  // 2. Ask before dangerous actions: <form data-confirm="Are you sure?">
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });

  // 3. Seat selection: show the chosen seat and enable the Continue button
  var seatInputs = document.querySelectorAll('.seat input[name="seat_id"]');
  var chosen = document.getElementById('chosen-seat');
  var btn = document.getElementById('continue-btn');
  seatInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      if (chosen) { chosen.textContent = input.getAttribute('data-label'); }
      if (btn) { btn.disabled = false; }
    });
  });

  // 4. Registration / password forms: check both passwords match
  document.querySelectorAll('form[data-match]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var p1 = form.querySelector('[name="password"]');
      var p2 = form.querySelector('[name="confirm_password"]');
      if (p1 && p2 && p1.value !== p2.value) {
        e.preventDefault();
        alert('Passwords do not match.');
      }
    });
  });

  // 5. Print button
  document.querySelectorAll('[data-print]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });

  // 6. Search form: do not allow the same origin and destination
  var search = document.querySelector('.search-form');
  if (search) {
    search.addEventListener('submit', function (e) {
      var f = search.querySelector('[name="from"]').value;
      var t = search.querySelector('[name="to"]').value;
      if (f && f === t) { e.preventDefault(); alert('Departure and destination must be different.'); }
    });
  }
});
