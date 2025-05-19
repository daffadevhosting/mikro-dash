// Cek status login

  function logout() {
    firebase.auth().signOut().then(function() {
      window.location.href = "/login";  // redirect ke halaman login setelah logout
    }).catch(function(error) {
      alert("Logout gagal: " + error.message);
    });
  }

  // Cek status login pengguna
  firebase.auth().onAuthStateChanged(function(user) {
    if (user) {
      console.log("Login sebagai:", user.email);
    } else {
      window.location.href = "/login"; // redirect ke halaman login jika tidak ada user
    }
  });
