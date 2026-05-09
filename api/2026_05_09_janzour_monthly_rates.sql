-- ═══════════════════════════════════════════════════════════════════════════
-- Janzour monthly rates (Processed_Parcels × Janzour_Stores)
-- Run in Supabase SQL Editor or: psql -f ...
--
-- Prerequisites: tables "Processed_Parcels" and "Janzour_Stores" exist with
--   Processed_Parcels: store_id, status, completion_date (timestamptz or date)
--   Janzour_Stores: store_id (join key; adjust column names if your schema differs)
-- ═══════════════════════════════════════════════════════════════════════════

-- ── Performance: partial composite index matches filter + join + GROUP BY month ──
-- Planner can seek by store_id and filter by completion_date; partial excludes
-- irrelevant rows early on large tables.
CREATE INDEX IF NOT EXISTS idx_processed_parcels_janzour_agg
  ON public."Processed_Parcels" (store_id, completion_date DESC)
  WHERE completion_date IS NOT NULL
    AND status IN ('تم التسليم', 'مخزن مرتجعات');

-- Standalone btree on completion_date for time-window scans (optional but cheap).
CREATE INDEX IF NOT EXISTS idx_processed_parcels_completion_date
  ON public."Processed_Parcels" (completion_date DESC)
  WHERE completion_date IS NOT NULL
    AND status IN ('تم التسليم', 'مخزن مرتجعات');

-- Janzour store lookup (unique when store_id is the natural key).
CREATE INDEX IF NOT EXISTS idx_janzour_stores_store_id
  ON public."Janzour_Stores" (store_id);


-- ── Aggregated monthly stats (only finalized statuses, janźour stores only) ──
CREATE OR REPLACE FUNCTION public.get_janzour_monthly_rates()
RETURNS TABLE (
  year integer,
  month integer,
  total_processed bigint,
  delivered_count bigint,
  returned_count bigint,
  delivery_rate_pct numeric,
  return_rate_pct numeric
)
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT
    EXTRACT(YEAR FROM pp.completion_date)::integer AS year,
    EXTRACT(MONTH FROM pp.completion_date)::integer AS month,
    COUNT(*)::bigint AS total_processed,
    COUNT(*) FILTER (WHERE pp.status = 'تم التسليم')::bigint AS delivered_count,
    COUNT(*) FILTER (WHERE pp.status = 'مخزن مرتجعات')::bigint AS returned_count,
    CASE WHEN COUNT(*) > 0
      THEN ROUND((100.0 * COUNT(*) FILTER (WHERE pp.status = 'تم التسليم') / COUNT(*))::numeric, 2)
      ELSE 0::numeric END AS delivery_rate_pct,
    CASE WHEN COUNT(*) > 0
      THEN ROUND((100.0 * COUNT(*) FILTER (WHERE pp.status = 'مخزن مرتجعات') / COUNT(*))::numeric, 2)
      ELSE 0::numeric END AS return_rate_pct
  FROM public."Processed_Parcels" pp
  INNER JOIN public."Janzour_Stores" js ON js.store_id = pp.store_id
  WHERE pp.completion_date IS NOT NULL
    AND pp.status IN ('تم التسليم', 'مخزن مرتجعات')
  GROUP BY EXTRACT(YEAR FROM pp.completion_date), EXTRACT(MONTH FROM pp.completion_date)
  ORDER BY EXTRACT(YEAR FROM pp.completion_date) DESC, EXTRACT(MONTH FROM pp.completion_date) DESC;
$$;

COMMENT ON FUNCTION public.get_janzour_monthly_rates() IS
  'Monthly delivered/returned counts for Janzour stores from Processed_Parcels (completion_date).';

-- PostgREST / Supabase client access
GRANT EXECUTE ON FUNCTION public.get_janzour_monthly_rates() TO anon, authenticated;

-- If you prefer SECURITY INVOKER instead (honors RLS on base tables), replace the
-- function with INVOKER and add SELECT policies on "Processed_Parcels" / "Janzour_Stores"
-- for the appropriate roles.
