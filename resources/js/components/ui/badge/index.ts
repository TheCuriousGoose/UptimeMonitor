import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

// A squared-off mono tag rather than the default rounded-full pill — reads as
// a status flag on a console rather than a marketing chip.
export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-[3px] border px-1.5 py-0.5 font-mono text-[10px] font-medium tracking-wide uppercase w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:ring-1 focus-visible:ring-ring aria-invalid:border-destructive transition-colors overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-primary/30 bg-primary/10 text-primary [a&]:hover:bg-primary/20",
        secondary:
          "border-border bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/80",
        destructive:
         "border-destructive/30 bg-destructive/10 text-destructive [a&]:hover:bg-destructive/20",
        success:
          "border-success/30 bg-success/10 text-success [a&]:hover:bg-success/20",
        outline:
          "text-muted-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
