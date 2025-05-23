/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/

#include "zbxsysinfo.h"
#include "../sysinfo.h"

static long	hertz = 0;

int	system_uptime(AGENT_REQUEST *request, AGENT_RESULT *result)
{
#if defined(HAVE_LIBPERFSTAT)
	perfstat_cpu_total_t	ps_cpu_total;

	ZBX_UNUSED(request);

	if (0 >= hertz)
	{
		hertz = sysconf(_SC_CLK_TCK);

		if (-1 == hertz)
		{
			SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot obtain clock-tick increment: %s",
					zbx_strerror(errno)));
			return SYSINFO_RET_FAIL;
		}

		if (0 == hertz)	/* make sure we do not divide by 0 */
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot calculate uptime because clock-tick increment"
					" is zero."));
			return SYSINFO_RET_FAIL;
		}
	}

	/* AIX 6.1 */
	if (-1 == perfstat_cpu_total(NULL, &ps_cpu_total, sizeof(ps_cpu_total), 1))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot obtain system information: %s", zbx_strerror(errno)));
		return SYSINFO_RET_FAIL;
	}

	SET_UI64_RESULT(result, (zbx_uint64_t)((double)ps_cpu_total.lbolt / hertz));

	return SYSINFO_RET_OK;
#else
	ZBX_UNUSED(request);

	SET_MSG_RESULT(result, zbx_strdup(NULL, "Agent was compiled without support for Perfstat API."));

	return SYSINFO_RET_FAIL;
#endif
}
