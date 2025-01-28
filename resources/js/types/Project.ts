export interface Project {
  uuid: string;
  name: string;
  description?: string;
  departmentUuid: string;
  managerId: number;
  startDate?: Date | string;
  dueDate?: Date | string;
  status?: 'active' | 'completed' | 'on_hold';
  priority?: string;
  completionPercentage?: number;
  isShared?: boolean;
  estimatedHours?: number;
  actualHours?: number;

  // Methods to calculate derived properties
  updateProgress?(): void;
  isOverdue?(): boolean;
  getEfficiencyRate?(): number;
}
