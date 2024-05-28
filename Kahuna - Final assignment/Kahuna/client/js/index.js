document.addEventListener('DOMContentLoaded', init);

const BASE_URI = 'http://localhost:8000/kahuna/api/';
let products = [];

function init() {
    setInitialColourMode();
    checkAndRedirect('home', loadProducts);

    // Add event listener to the logout button
    document.getElementById('logoutBtn').addEventListener('click', logout);

    // Add event listener to the view all products button
    document.getElementById('viewAllProductsBtn').addEventListener('click', viewAllProducts);
}

function setInitialColourMode() {
    let colorMode = localStorage.getItem("kahuna_color")
    if (colorMode) {
        toggleColourMode(colorMode);
    } else {
        toggleColourMode(window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light');
    }
}

function toggleColourMode(mode) {
    document.documentElement.setAttribute("data-bs-theme", mode);
    const switcher = document.getElementById('color-switch-area');
    if (mode === 'dark') {
        switcher.innerHTML = '<i class="bi-moon-stars-fill"></i>';
    } else {
        switcher.innerHTML = '<i class="bi-sun-fill"></i>';
    }
    localStorage.setItem("product_color", mode);
}

async function showView(view) {
    if (view) {
        return fetch(`includes/${view}.html`)
        .then(res => res.text())
        .then(html => document.getElementById('mainContent').innerHTML = html);
    }
    return null;
}

async function isValidToken(token, user, cb) {
    return fetch(`${BASE_URI}token`, {
        headers: {
            'X-Api-Key': token,
            'X-Api-User': user
        }
    })
    .then(res => res.json())
    .then(res => cb(res.data.valid));
}

function getFormData(object) {
    const formData = new FormData();
    Object.keys(object).forEach(key => formData.append(key, object[key]));
    return formData;
}

function checkAndRedirect(redirect = null, cb = null) {
    let token = localStorage.getItem("product_token");

    if (!token) {
        showView('login').then(() => bindLogin(redirect, cb));
    } else {
        let user = localStorage.getItem("product_user");
        isValidToken(token, user, (valid) => {
            if (valid) {
                showView(redirect).then(cb);
            } else {
                showView('login').then(() => bindLogin(redirect, cb));
            }
        });
    }
}

function bindLogin(redirect, cb) {
    document.getElementById('loginForm').addEventListener('submit', (evt) => {
        evt.preventDefault();
        fetch(`${BASE_URI}login`, {
            mode: 'cors',
            method: 'POST',
            body: new FormData(document.getElementById('loginForm'))
        })
        .then(res => res.json())
        .then(res => {
            localStorage.setItem('product_token', res.data.token);
            localStorage.setItem('product_user', res.data.user);
            localStorage.setItem('product_access_level', res.data.accessLevel); // Store access level
            showView(redirect).then(cb);
        })
        .catch(err => showMessage(err, 'danger'));
    });
}
//listing products available to register
function fillProductDetails() {
    const selectElement = document.getElementById('productSelection');
    const selectedProductName = selectElement.options[selectElement.selectedIndex].value;

    switch (selectedProductName) {
        case 'Product1':
            document.getElementById('productName').value = 'CombiSpin Washing Machine';
            document.getElementById('productSerial').value = 'KHWM8199911';
            document.getElementById('productWarranty').value = '2';
            break;

            case 'Product2':
                document.getElementById('productName').value = 'CombiSpin + Dry Washing Machine';
                document.getElementById('productSerial').value = 'KHWM8199912';
                document.getElementById('productWarranty').value = '2';
                break;

            case 'Product3':
                document.getElementById('productName').value = 'CombiGrill Microwave';
                document.getElementById('productSerial').value = 'KHMW789991';
                document.getElementById('productWarranty').value = '1';
                break;

             case 'Product4':
                document.getElementById('productName').value = 'K5 Water Pump';
                document.getElementById('productSerial').value = 'KHWP890001';
                document.getElementById('productWarranty').value = '5';
                break;

             case 'Product5':
                document.getElementById('productName').value = 'K5 Heated Water Pump';
                document.getElementById('productSerial').value = 'KHWP890002';
                document.getElementById('productWarranty').value = '5';
                break;

              case 'Product6':
               document.getElementById('productName').value = 'Smart Switch Lite';
               document.getElementById('productSerial').value = 'KHSS988881';
               document.getElementById('productWarranty').value = '2';
               break;

             case 'Product7':
                document.getElementById('productName').value = 'Smart Switch Pro';
                document.getElementById('productSerial').value = 'KHSS988882';
                document.getElementById('productWarranty').value = '2';
                break;

             case 'Product8':
                document.getElementById('productName').value = 'Smart Switch Pro V2';
                document.getElementById('productSerial').value = 'KHSS988883';
                document.getElementById('productWarranty').value = '2';
                break;

             case 'Product9':
                document.getElementById('productName').value = 'Smart Heated Mug';
                document.getElementById('productSerial').value = 'KHHM89762';
                document.getElementById('productWarranty').value = '1';
                break;

             case 'Product10':
                document.getElementById('productName').value = 'Smart Bulb 001';
                document.getElementById('productSerial').value = 'KHSB0001';
                document.getElementById('productWarranty').value = '1';
                break;


        default:
            document.getElementById('productName').value = '';
            document.getElementById('productSerial').value = '';
            document.getElementById('productWarranty').value = '';
            break;
    }
}


    function bindHome() {

  // Add event listener to the view all products button
     document.getElementById('viewAllProductsBtn').addEventListener('click', viewAllProducts);

    document.getElementById('productForm').addEventListener('submit', (evt) => {
        evt.preventDefault();

        productData = new FormData(document.getElementById('productForm'));
        checkAndRedirect('home', () => {
            fetch(`${BASE_URI}product`, {
                mode: 'cors',
                method: 'POST',
                headers: {
                    'X-Api-Key': localStorage.getItem("product_token"),
                    'X-Api-User': localStorage.getItem("product_user")
                },
                body: productData   
            })
            .then(loadProducts)
            .catch(err => showMessage(err, 'danger'));

        });
    });
}



function loadProducts() {
    checkAndRedirect('home', () => {
        fetch(`${BASE_URI}product`, {
            mode: 'cors',
            method: 'GET',
            headers: {
                'X-Api-Key': localStorage.getItem("product_token"),
                'X-Api-User': localStorage.getItem("product_user")
            }
        })
        .then(res => res.json())
        .then(res => {
            products = res.data;
            displayProducts();
            bindHome();
        })
        .catch(err => showMessage(err, 'danger'));
    });
}

function displayProducts() {
    let html = '';
    if (products.length === 0) {
        html = '<p>You have no products yet!</p>';
    } else {
        html = `<ul class="list-group">`;
        for (const product of products) {
            html += `<li class="list-group-item">
                <div class="row">
                    <div class="col-4">
                        <strong>Name:</strong> ${product.name}
                    </div>
                    <div class="col-4">
                        <strong>Serial Number:</strong> ${product.serial}
                    </div>
                    <div class="col-3">
                        <strong>Warranty Length:</strong> ${product.warrantyLength}
                    </div>
                    <div class="col-1">
                        <button onclick="deleteProduct('${product.id}')" class="btn btn-danger"><i class="bi bi-trash-fill"></i></button>
                    </div>
                </div>
            </li>`;
        }
        html += '</ul>';
    }
    document.getElementById('product-items').innerHTML = html;
}

function deleteProduct(productId) {
    const confirmed = confirm("Are you sure you want to delete this product?");
    if (!confirmed) return; // Do nothing if the user cancels

    checkAndRedirect('home', () => {
        fetch(`${BASE_URI}product/${productId}`, {
            mode: 'cors',
            method: 'DELETE',
            headers: {
                'X-Api-Key': localStorage.getItem("product_token"),
                'X-Api-User': localStorage.getItem("product_user")
            }
        })
        .then(loadProducts)
        .catch(err => showMessage(err, 'danger'));
    });
}

function checkProduct(evt, localProductId) {
    const product = products[localProductId];
    product.complete = evt.target.checked;
    checkAndRedirect('home', () => {
        fetch(`${BASE_URI}product`, {
            mode: 'cors',
            method: 'PATCH',
            headers: {
                'X-Api-Key': localStorage.getItem("product_token"),
                'X-Api-User': localStorage.getItem("product_user")
            },
            body: getFormData(product)
        })
        .then(loadProducts)
        .catch(err => showMessage(err, 'danger'));
    });
}

function registerUser() {
    showView('register').then(() => {
        document.getElementById('registerForm').addEventListener('submit', (evt) => {
            evt.preventDefault();
            fetch(`${BASE_URI}user`, {
                mode: 'cors',
                method: 'POST',
                body: new FormData(document.getElementById('registerForm'))
            })
            .then(showView('login').then(() => bindLogin('home', bindHome)))
            .catch(err => showMessage(err, 'danger'));
        });
    });
}

function showMessage(msg) {
    console.log(msg);
}

function logout() {
    // Clear the user's token and user data from localStorage
    localStorage.removeItem('product_token');
    localStorage.removeItem('product_user');
    localStorage.removeItem('product_access_level'); // Clear access level

    // Redirect the user to the login page
    window.location.href = 'login.html'; 
}

function viewAllProducts() {
    checkAndRedirect('home', () => {
        const accessLevel = localStorage.getItem('product_access_level');
        if (accessLevel === 'admin') {
            fetch(`${BASE_URI}product`, {
                mode: 'cors',
                method: 'GET',
                headers: {
                    'X-Api-Key': localStorage.getItem("product_token"),
                    'X-Api-User': localStorage.getItem("product_user")
                }
            })
            .then(res => res.json())
            .then(res => {
                products = res.data;
                displayProducts();
            })
            .catch(err => showMessage(err, 'danger'));
        } 
    });
}



function init() {
    setInitialColourMode();
    checkAndRedirect('home', loadProducts);

    // Add event listener to the logout button
    document.getElementById('logoutBtn').addEventListener('click', logout);

  
    
}





