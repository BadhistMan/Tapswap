// script.js
document.addEventListener('DOMContentLoaded', () => {
    // State variables
    let userData = null;
    let constants = {};
    let energyInterval = null;

    // Element references
    const authSection = document.getElementById('auth-section');
    const mainContent = document.getElementById('main-content');
    const bottomNav = document.getElementById('bottom-nav');
    
    // Auth elements
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
    const logoutBtn = document.getElementById('logout-btn');

    // Display elements
    const pointsValue = document.getElementById('points-value');
    const energyValue = document.getElementById('energy-value');
    const profileUsername = document.getElementById('profile-username');
    const profilePoints = document.getElementById('profile-points');
    
    // Interactive elements
    const catTapper = document.getElementById('cat-tapper');
    const navItems = document.querySelectorAll('.nav-item');
    const pageSections = document.querySelectorAll('.page-section');

    // Withdraw elements
    const withdrawPoints = document.getElementById('withdraw-points');
    const minWithdrawPoints = document.getElementById('min-withdraw-points');
    const withdrawBtn = document.getElementById('withdraw-btn');
    const withdrawAmount = document.getElementById('withdraw-amount');
    const withdrawalHistoryDiv = document.getElementById('withdrawal-history');


    // --- API Helper ---
    async function apiCall(action, body = null) {
        const options = {
            method: 'POST',
            body: body
        };
        try {
            const response = await fetch(`api.php?action=${action}`, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API Call Error:', error);
            return { success: false, message: `Client-side error: ${error.message}` };
        }
    }

    // --- UI Update Functions ---
    function updateUI() {
        if (!userData) return;
        
        pointsValue.textContent = userData.points.toLocaleString();
        energyValue.textContent = `${userData.energy}/${userData.max_energy}`;
        
        profileUsername.textContent = userData.username;
        profilePoints.textContent = userData.points.toLocaleString();

        withdrawPoints.textContent = userData.points.toLocaleString();
        minWithdrawPoints.textContent = constants.MIN_WITHDRAW_POINTS.toLocaleString();
        withdrawAmount.textContent = constants.MIN_WITHDRAW_POINTS.toLocaleString();
        
        withdrawBtn.disabled = userData.points < constants.MIN_WITHDRAW_POINTS;
    }

    // --- Energy Regeneration ---
    function startEnergyRegen() {
        if (energyInterval) clearInterval(energyInterval);
        
        energyInterval = setInterval(() => {
            if (userData.energy < userData.max_energy) {
                userData.energy = Math.min(userData.max_energy, userData.energy + 1);
                updateUI();
            } else {
                clearInterval(energyInterval);
                energyInterval = null;
            }
        }, 1000 / constants.ENERGY_REGEN_RATE);
    }
    
    // --- Page Navigation ---
    function switchPage(targetSectionId) {
        pageSections.forEach(section => {
            section.classList.remove('active');
        });
        navItems.forEach(item => {
            item.classList.remove('active');
        });

        const targetSection = document.getElementById(targetSectionId);
        const targetNavItem = document.querySelector(`.nav-item[data-section="${targetSectionId}"]`);
        
        if (targetSection) targetSection.classList.add('active');
        if (targetNavItem) targetNavItem.classList.add('active');

        // Fetch history when switching to withdraw page
        if (targetSectionId === 'withdraw-section') {
            fetchWithdrawalHistory();
        }
    }

    // --- Feature Implementations ---
    async function checkSession() {
        const result = await apiCall('check_session', new FormData());
        if (result.success) {
            userData = result.user;
            constants = result.constants;
            authSection.style.display = 'none';
            mainContent.style.display = 'block';
            bottomNav.style.display = 'flex';
            updateUI();
            startEnergyRegen();
            populateTasks();
        } else {
            authSection.style.display = 'block';
            mainContent.style.display = 'none';
            bottomNav.style.display = 'none';
        }
    }
    
    async function handleLogin() {
        const formData = new FormData();
        formData.append('username', document.getElementById('login-username').value);
        formData.append('password', document.getElementById('login-password').value);
        
        const result = await apiCall('login', formData);
        alert(result.message);
        if (result.success) {
            checkSession();
        }
    }
    
    async function handleRegister() {
        const formData = new FormData();
        formData.append('username', document.getElementById('register-username').value);
        formData.append('password', document.getElementById('register-password').value);

        const result = await apiCall('register', formData);
        alert(result.message);
        if (result.success) {
            showLoginLink.click();
        }
    }
    
    async function handleLogout() {
        await apiCall('logout', new FormData());
        userData = null;
        if(energyInterval) clearInterval(energyInterval);
        checkSession();
    }
    
    async function handleTap(event) {
        if (userData.energy > 0) {
            const result = await apiCall('tap', new FormData());
            
            // Visual feedback for tap
            const feedback = document.createElement('div');
            feedback.className = 'tap-feedback';
            feedback.textContent = '+1';
            feedback.style.left = `${event.clientX - catTapper.getBoundingClientRect().left}px`;
            feedback.style.top = `${event.clientY - catTapper.getBoundingClientRect().top}px`;
            catTapper.appendChild(feedback);
            setTimeout(() => feedback.remove(), 1000);

            if (result.success) {
                userData.points = result.points;
                userData.energy = result.energy;
                updateUI();
                if (!energyInterval) startEnergyRegen(); // Restart regen if it was stopped
            } else {
                alert(result.message);
            }
        } else {
            alert("You are out of energy!");
        }
    }

    function populateTasks() {
        const taskList = document.getElementById('task-list');
        taskList.innerHTML = '';
        const tasks = [
            { id: 1, text: `Watch Video X & Earn ${constants.POINTS_PER_TASK} Points`, url: 'https://example.com/videoX' },
            { id: 2, text: `Follow us on Twitter & Earn ${constants.POINTS_PER_TASK} Points`, url: 'https://twitter.com' },
            { id: 3, text: `Join our Discord & Earn ${constants.POINTS_PER_TASK} Points`, url: 'https://discord.com' },
            { id: 4, text: `Subscribe to Newsletter & Earn ${constants.POINTS_PER_TASK} Points`, url: 'https://example.com/newsletter' }
        ];

        tasks.forEach(task => {
            const taskEl = document.createElement('div');
            taskEl.className = 'task';
            taskEl.innerHTML = `
                <span>${task.text}</span>
                <button data-task-id="${task.id}" data-url="${task.url}">Claim Reward</button>
            `;
            taskList.appendChild(taskEl);
        });
    }

    async function handleCompleteTask(e) {
        if (e.target.tagName === 'BUTTON') {
            const taskId = e.target.dataset.taskId;
            const url = e.target.dataset.url;
            
            // Optional: open link
            // window.open(url, '_blank');
            
            const formData = new FormData();
            formData.append('taskId', taskId);

            const result = await apiCall('completeTask', formData);
            alert(result.message);
            if (result.success) {
                userData.points = result.points;
                updateUI();
            }
        }
    }
    
    async function handleWithdraw() {
        if (confirm(`Are you sure you want to withdraw ${constants.MIN_WITHDRAW_POINTS} points?`)) {
            const result = await apiCall('withdraw', new FormData());
            alert(result.message);
            if (result.success) {
                userData.points = result.points;
                updateUI();
                fetchWithdrawalHistory(); // Refresh history
            }
        }
    }
    
    async function fetchWithdrawalHistory() {
        const result = await apiCall('getWithdrawalHistory', new FormData());
        if (result.success && result.history) {
            if (result.history.length > 0) {
                withdrawalHistoryDiv.innerHTML = result.history.map(item => `
                    <div class="history-item">
                        Withdrew ${parseInt(item.points_withdrawn).toLocaleString()} points on 
                        ${new Date(item.withdrawal_timestamp).toLocaleString()}
                    </div>
                `).join('');
            } else {
                withdrawalHistoryDiv.innerHTML = '<p>No withdrawal history.</p>';
            }
        }
    }

    // --- Event Listeners ---
    showRegisterLink.addEventListener('click', (e) => {
        e.preventDefault();
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    });
    showLoginLink.addEventListener('click', (e) => {
        e.preventDefault();
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
    });

    loginBtn.addEventListener('click', handleLogin);
    registerBtn.addEventListener('click', handleRegister);
    logoutBtn.addEventListener('click', handleLogout);
    
    catTapper.addEventListener('click', handleTap);
    withdrawBtn.addEventListener('click', handleWithdraw);

    document.getElementById('task-list').addEventListener('click', handleCompleteTask);
    
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSectionId = item.getAttribute('data-section');
            switchPage(targetSectionId);
        });
    });

    // --- Initial Load ---
    checkSession();
});