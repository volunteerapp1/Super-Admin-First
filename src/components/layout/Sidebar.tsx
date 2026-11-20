import { LayoutDashboard, Building2, Settings, Droplet, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useTheme } from '@/context/ThemeContext';

export type NavView = 'overview' | 'tenants' | 'limits';

interface SidebarProps {
  collapsed: boolean;
  onToggleCollapse: () => void;
  currentView: NavView;
  onNavigate: (view: NavView) => void;
  mobileOpen: boolean;
  onCloseMobile: () => void;
}

const navItems: { id: NavView; label: string; icon: typeof LayoutDashboard }[] = [
  { id: 'overview', label: 'Dashboard', icon: LayoutDashboard },
  { id: 'tenants', label: 'Blood Banks', icon: Building2 },
  { id: 'limits', label: 'Package Limits', icon: Settings },
];

export function Sidebar({
  collapsed,
  onToggleCollapse,
  currentView,
  onNavigate,
  mobileOpen,
  onCloseMobile,
}: SidebarProps) {
  const { theme } = useTheme();

  return (
    <>
      {mobileOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
          onClick={onCloseMobile}
        />
      )}

      <aside
        className={`
          fixed lg:sticky top-0 z-40 h-screen flex flex-col
          bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800
          transition-all duration-300 ease-in-out
          ${collapsed ? 'lg:w-[72px]' : 'lg:w-64'}
          ${mobileOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64 lg:translate-x-0'}
        `}
      >
        {/* Logo header */}
        <div className="flex items-center gap-3 px-4 h-16 border-b border-gray-200 dark:border-gray-800 shrink-0">
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-brand-500 shrink-0 shadow-lg shadow-brand-500/30">
            <Droplet className="w-6 h-6 text-white" fill="white" />
          </div>
          {!collapsed && (
            <div className="overflow-hidden">
              <h1 className="font-bold text-base text-gray-900 dark:text-white leading-tight">BloodLink</h1>
              <p className="text-xs text-gray-500 dark:text-gray-400 leading-tight">Super Admin</p>
            </div>
          )}
          <button
            onClick={onCloseMobile}
            className="ml-auto lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin">
          {!collapsed && (
            <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
              Management
            </p>
          )}
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = currentView === item.id;
            return (
              <button
                key={item.id}
                onClick={() => {
                  onNavigate(item.id);
                  onCloseMobile();
                }}
                className={`
                  w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                  transition-all duration-200 group relative
                  ${isActive
                    ? 'bg-brand-50 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60 hover:text-gray-900 dark:hover:text-gray-200'
                  }
                `}
                title={collapsed ? item.label : undefined}
              >
                {isActive && (
                  <span className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-brand-500" />
                )}
                <Icon className={`w-5 h-5 shrink-0 ${isActive ? 'text-brand-500' : ''}`} />
                {!collapsed && <span className="truncate">{item.label}</span>}
              </button>
            );
          })}
        </nav>

        {/* Collapse toggle (desktop) */}
        <div className="hidden lg:block border-t border-gray-200 dark:border-gray-800 shrink-0">
          <button
            onClick={onToggleCollapse}
            className="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors"
          >
            {collapsed ? (
              <ChevronRight className="w-5 h-5 mx-auto" />
            ) : (
              <>
                <ChevronLeft className="w-5 h-5" />
                <span>Collapse</span>
              </>
            )}
          </button>
        </div>

        {/* Theme indicator footer */}
        <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-800 shrink-0">
          <div className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
            {!collapsed && (
              <span className="text-xs text-gray-500 dark:text-gray-400">System operational</span>
            )}
          </div>
        </div>
      </aside>
    </>
  );
}
