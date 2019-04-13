#!/usr/bin/env python3
import glob
import os.path as path
import subprocess
import os, sys
import time

pugdirs = [
	'application/views/exam/*.pug',
	'application/views/admin/*.pug'
]
exclude = {'common.pug'}
defext = '.html'
newext = '.php'
while 1:
	for pugdir in pugdirs:
		for pathn in glob.iglob(pugdir):
			filename = path.basename(pathn)
			out_path = path.splitext(pathn)[0] + newext
			if filename in exclude:
				continue
			try:
				if path.getmtime(out_path) > path.getmtime(pathn):
					continue
			except FileNotFoundError:
				pass
			ret = subprocess.call(['pug',pathn],shell=True)
			if ret == 0:
				try:
					os.unlink(out_path)
				except FileNotFoundError:
					pass
				os.rename(path.splitext(pathn)[0] + defext, out_path)
			else:
				sys.exit(1)
	if len(sys.argv) > 1 and sys.argv[1] == 'watch':
		time.sleep(0.5)
		continue
	break