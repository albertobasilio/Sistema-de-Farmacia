// farmacias/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginContainer = document.querySelector('.login-container');
    const registerContainer = document.querySelector('.register-container');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');
    
    // Alternar entre login e registro
    showRegisterLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginContainer.style.display = 'none';
        registerContainer.style.display = 'block';
    });
    
    showLoginLink.addEventListener('click', function(e) {
        e.preventDefault();
        registerContainer.style.display = 'none';
        loginContainer.style.display = 'block';
    });
    
    // Processar login
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const senha = document.getElementById('senha').value;
        
        // Simulação de autenticação - em um sistema real, seria uma chamada AJAX
        if (email && senha) {
            // Redirecionar para o dashboard (simulação)
            window.location.href = 'dashboard.html';
        } else {
            alert('Por favor, preencha todos os campos');
        }
    });
    
    // Processar registro
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nome = document.getElementById('reg-nome').value;
        const endereco = document.getElementById('reg-endereco').value;
        const telefone = document.getElementById('reg-telefone').value;
        const email = document.getElementById('reg-email').value;
        const senha = document.getElementById('reg-senha').value;
        const confirmarSenha = document.getElementById('reg-confirmar-senha').value;
        
        // Validações
        if (senha !== confirmarSenha) {
            alert('As senhas não coincidem');
            return;
        }
        
        if (!nome || !endereco || !telefone || !email || !senha) {
            alert('Por favor, preencha todos os campos obrigatórios');
            return;
        }
        
        // Simulação de registro - em um sistema real, seria uma chamada AJAX
        alert('Farmácia cadastrada com sucesso! Agora faça login.');
        registerContainer.style.display = 'none';
        loginContainer.style.display = 'block';
        registerForm.reset();
    });
});