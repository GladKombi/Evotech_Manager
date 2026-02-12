<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evotech_Africa - Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles personnalisés */
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #10b981;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
        
        .card-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
        
        /* Animation pour les cartes */
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Scrollbar personnalisée */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 min-h-screen fixed left-0 top-0 z-40 text-white hidden lg:block">
            <div class="p-6">
                <h1 class="text-2xl font-bold flex items-center">
                    <i class="fas fa-rocket mr-2 text-blue-400"></i>
                    Evotech
                </h1>
            </div>
            
            <nav class="mt-6 px-4">
                <div class="mb-8">
                    <p class="text-xs uppercase text-gray-400 tracking-wider mb-4 px-4">Main</p>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg bg-blue-900/30 text-blue-300 mb-2">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-800/50 mb-2">
                        <i class="fas fa-shopping-cart mr-3"></i>
                        Orders
                    </a>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-800/50 mb-2">
                        <i class="fas fa-box mr-3"></i>
                        Products
                    </a>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-800/50 mb-2">
                        <i class="fas fa-users mr-3"></i>
                        Customers
                    </a>
                </div>
                
                <div class="mb-8">
                    <p class="text-xs uppercase text-gray-400 tracking-wider mb-4 px-4">Analytics</p>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-800/50 mb-2">
                        <i class="fas fa-chart-bar mr-3"></i>
                        Reports
                    </a>
                    <a href="#" class="flex items-center py-3 px-4 rounded-lg hover:bg-gray-800/50 mb-2">
                        <i class="fas fa-chart-line mr-3"></i>
                        Analytics
                    </a>
                </div>
            </nav>
            
            <div class="absolute bottom-0 w-full p-6 border-t border-gray-700/50">
                <div class="flex items-center">
                    <img src="https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff" 
                         class="w-10 h-10 rounded-full" alt="Avatar">
                    <div class="ml-3">
                        <p class="font-medium">John Doe</p>
                        <p class="text-xs text-gray-400">Admin</p>
                    </div>
                    <button class="ml-auto text-gray-400 hover:text-white">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar toggle -->
        <div class="lg:hidden fixed top-4 left-4 z-50">
            <button id="sidebarToggle" class="bg-gray-800 text-white p-3 rounded-lg">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Main content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top navbar -->
            <nav class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                        <span class="ml-4 text-sm text-gray-500 hidden md:inline">A good dashboard to display your statistics</span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                        </div>
                        
                        <!-- Messages -->
                        <div class="relative">
                            <button class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                                <i class="fas fa-envelope text-xl"></i>
                                <span class="absolute top-1 right-1 w-2 h-2 bg-blue-500 rounded-full"></span>
                            </button>
                        </div>
                        
                        <!-- User menu -->
                        <div class="relative">
                            <button class="flex items-center space-x-2 focus:outline-none">
                                <img src="https://ui-avatars.com/api/?name=John+Doe&background=3b82f6&color=fff" 
                                     class="w-9 h-9 rounded-full" alt="Avatar">
                                <span class="hidden md:inline font-medium">John Doe</span>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="p-6">
                <!-- Stats cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Balance Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">BALANCE</p>
                                <h3 class="text-2xl font-bold mt-2">$50</h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <i class="fas fa-wallet text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">REVENUE</p>
                                <h3 class="text-2xl font-bold mt-2">$532.2</h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-lg">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">ORDERS</p>
                                <h3 class="text-2xl font-bold mt-2">1,544</h3>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-lg">
                                <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Today Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">SALES TODAY</p>
                                <h3 class="text-2xl font-bold mt-2">423</h3>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-lg">
                                <i class="fas fa-tags text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-500 rounded-full" style="width: 45%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Sales Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 card-hover">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold">Sales Overview</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-lg">Monthly</button>
                                <button class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-lg">Yearly</button>
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-8">
                            <div class="mr-8">
                                <h2 class="text-3xl font-bold">$21,102</h2>
                                <p class="text-green-600 text-sm mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i> +19% from last month
                                </p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                    <span class="text-sm">Current Month</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-gray-300 rounded-full mr-2"></div>
                                    <span class="text-sm">Last Month</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chart placeholder -->
                        <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <i class="fas fa-chart-bar text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">Sales chart visualization</p>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h3 class="text-lg font-semibold mb-6">Your Earnings</h3>
                        
                        <!-- Radial chart placeholder -->
                        <div class="h-48 w-48 mx-auto relative mb-6">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <p class="text-3xl font-bold">75%</p>
                                    <p class="text-gray-500 text-sm">Completed</p>
                                </div>
                            </div>
                            <svg class="w-full h-full" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#10b981" stroke-width="10" 
                                        stroke-dasharray="251.2" stroke-dashoffset="62.8" stroke-linecap="round"
                                        transform="rotate(-90 50 50)"/>
                            </svg>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-gray-500 text-sm">From last month</p>
                            <h2 class="text-2xl font-bold text-green-600 mt-1">+$2,134</h2>
                        </div>
                    </div>
                </div>

                <!-- Tables and Progress -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Orders Table -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden card-hover">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold">Recent Orders</h3>
                                <button class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-download mr-2"></i> Export
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Name</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Email</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Phone</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">Graiden</td>
                                        <td class="py-3 px-4 text-gray-600">graiden@example.com</td>
                                        <td class="py-3 px-4 text-gray-600">(076) 4820 8838</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">Dale</td>
                                        <td class="py-3 px-4 text-gray-600">dale@example.com</td>
                                        <td class="py-3 px-4 text-gray-600">(0500) 527693</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">Nathaniel</td>
                                        <td class="py-3 px-4 text-gray-600">nathaniel@example.com</td>
                                        <td class="py-3 px-4 text-gray-600">(012165) 76278</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Inactive</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4">Darius</td>
                                        <td class="py-3 px-4 text-gray-600">darius@example.com</td>
                                        <td class="py-3 px-4 text-gray-600">(0309) 690 7871</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-4 border-t border-gray-200 text-center">
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View all orders →
                            </button>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                        <h3 class="text-lg font-semibold mb-6">Task Progress</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">UI Design</span>
                                    <span class="text-sm font-medium">60%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full progress-bar" style="width: 60%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">VueJS</span>
                                    <span class="text-sm font-medium">35%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full progress-bar" style="width: 35%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">Laravel</span>
                                    <span class="text-sm font-medium">50%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full progress-bar" style="width: 50%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">ReactJS</span>
                                    <span class="text-sm font-medium">80%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full progress-bar" style="width: 80%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium">Go</span>
                                    <span class="text-sm font-medium">65%</span>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500 rounded-full progress-bar" style="width: 65%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-6 py-4 mt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-600 mb-2 md:mb-0">© 2023 Evotech Africa. All rights reserved.</p>
                    <p class="text-gray-600">
                        Crafted with <i class="fas fa-heart text-red-500 mx-1"></i> by 
                        <a href="https://evotech.africa" class="text-blue-600 hover:text-blue-800 ml-1">Evotech Team</a>
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Mobile sidebar overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    <script>
        // Gestion de la sidebar mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.add('hidden');
            this.classList.add('hidden');
        });

        // Animation des barres de progression au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });

        // Simulation de données en temps réel
        function updateStats() {
            const stats = document.querySelectorAll('.text-2xl');
            // Simuler une mise à jour aléatoire
            stats.forEach(stat => {
                const current = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
                if (current < 1000) {
                    const newValue = current + Math.floor(Math.random() * 10);
                    stat.textContent = '$' + newValue;
                }
            });
        }

        // Mettre à jour les stats toutes les 10 secondes (simulation)
        setInterval(updateStats, 10000);
    </script>
</body>
</html>