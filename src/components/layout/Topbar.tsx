import { Menu, Moon, Sun, Bell, Search, Plus } from 'lucide-react';
import { useTheme } from '@/context/ThemeContext';
import type { NavView } from './Sidebar';

interface TopbarProps {
  onOpenMobile: () => void;
  currentView: NavView;
  onAddBloodBank?: () => void;
  showAddButton?: boolean;
}

const viewTitles: Record<NavView, { title: string; subtitle: string }> = {
  overview: { title: 'Dashboard Overview', subtitle: 'Platform-wide metrics and tenant summary' },
  tenants: { title: 'Blood Bank Management', subtitle: 'Provision and manage tenant organizations' },
  limits: { title: 'Global Package Limits', subtitle: 'Configure donor and volunteer limits per package' },
};

export function Topbar({ onOpenMobile, currentView, onAddBloodBank, showAddButton }: TopbarProps) {
  const { theme, toggleTheme } = useTheme();
  const { title, subtitle } = viewTitles[currentView];

  return (
    <header className="sticky top-0 z-20 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800">
      <div className="flex items-center justify-between gap-4 px-4 sm:px-6 h-16">
        {/* Left: mobile menu + title */}
        <div className="flex items-center gap-3 min-w-0">
          <button
            onClick={onOpenMobile}
            className="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 shrink-0"
          >
            <Menu className="w-5 h-5" />
          </button>
          <div className="min-w-0">
            <h2 className="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{title}</h2>
            <p className="hidden sm:block text-xs text-gray-500 dark:text-gray-400 truncate">{subtitle}</p>
          </div>
        </div>

        {/* Right: search, add, notifications, theme */}
        <div className="flex items-center gap-2 sm:gap-3 shrink-0">
          <div className="hidden md:flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 w-56 lg:w-64">
            <Search className="w-4 h-4 text-gray-400 shrink-0" />
            <input
              type="text"
              placeholder="Search..."
              className="bg-transparent text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 outline-none w-full"
            />
          </div>

          {showAddButton && onAddBloodBank && (
            <button
              onClick={onAddBloodBank}
              className="hidden sm:flex items-center gap-2 px-3 lg:px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-lg shadow-brand-500/20 transition-all hover:shadow-brand-500/30 active:scale-95"
            >
              <Plus className="w-4 h-4" />
              <span className="hidden lg:inline">Add Blood Bank</span>
              <span className="lg:hidden">Add</span>
            </button>
          )}

          <button className="relative p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            <Bell className="w-5 h-5" />
            <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-brand-500 ring-2 ring-white dark:ring-gray-900" />
          </button>

          <button
            onClick={toggleTheme}
            className="p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            aria-label="Toggle theme"
          >
            {theme === 'dark' ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
          </button>

          <div className="flex items-center gap-2 pl-2 sm:pl-3 border-l border-gray-200 dark:border-gray-700">
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-xs font-bold shadow-md shrink-0">
              SA
            </div>
            <div className="hidden lg:block">
              <p className="text-sm font-semibold text-gray-900 dark:text-white leading-tight">Super Admin</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 leading-tight">admin@bloodlink.io</p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
